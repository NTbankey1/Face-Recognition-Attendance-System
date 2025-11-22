const FACE_SERVICE_URL = window.FACE_SERVICE_URL || 'http://localhost:8001';
const CAPTURE_WIDTH = 640;
const CAPTURE_HEIGHT = 480;
const STABILITY_REQUIRED = 2;
const CAPTURE_INTERVAL_MS = 750;
const HEALTH_CHECK_FRESHNESS_MS = 15000;
const REQUEST_TIMEOUT_MS = 6000;
const MAX_RECOGNIZED_HISTORY = 6;

let videoElement = null;
let videoStream = null;
let overlayCanvas = null;
let overlayCtx = null;
let captureCanvas = null;
let captureCtx = null;
let captureIntervalId = null;
let sendingFrame = false;
let stabilityMap = new Map();
let markedOnce = new Set();
let videoContainer = null;
let lastHealthCheck = 0;
let serviceHealthy = false;
let serviceErrorNotified = false;
let serviceStatusText = null;
let serviceStatusDot = null;
let modelsLoaderEl = null;
let recognizedListEl = null;
let recognizedEmptyEl = null;
let recognizedHistory = [];
let guideElement = null;
let guideLabel = null;
let guideTarget = null;
let guideCurrent = null;
let guideHideTimeout = null;

function resetMarked() {
  markedOnce.clear();
  stabilityMap.clear();
}

function updateCounts() {
  const table = document.querySelector('#studentTableContainer table');
  const totalEl = document.getElementById('totalCount');
  const presentEl = document.getElementById('presentCount');
  if (!table || !totalEl || !presentEl) return;
  const rows = table.querySelectorAll('tbody tr');
  const total = rows.length;
  let present = 0;
  rows.forEach((row) => {
    const statusCell = row.cells[5];
    if (!statusCell) return;
    const value = statusCell.innerText.trim().toLowerCase();
    if (value === 'có mặt' || value === 'present') {
      present += 1;
    }
  });
  totalEl.textContent = total;
  presentEl.textContent = present;
}

function showLoader(show) {
  if (!modelsLoaderEl) {
    modelsLoaderEl = document.getElementById('modelsLoader');
  }
  if (modelsLoaderEl) modelsLoaderEl.style.display = show ? 'inline' : 'none';
}

async function startWebcam() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: {
        width: { ideal: CAPTURE_WIDTH },
        height: { ideal: CAPTURE_HEIGHT },
        facingMode: 'user'
      },
      audio: false
    });
    if (!videoElement) videoElement = document.getElementById('video');
    videoElement.srcObject = stream;
    videoStream = stream;
    await videoElement.play();
  } catch (error) {
    console.error('Error accessing webcam', error);
    showMessage('Không thể truy cập webcam. Hãy cấp quyền camera và thử lại.');
    throw error;
  }
}

function stopWebcam() {
  if (captureIntervalId) {
    clearInterval(captureIntervalId);
    captureIntervalId = null;
  }
  sendingFrame = false;
  resetMarked();
  clearOverlay();
  if (videoStream) {
    videoStream.getTracks().forEach((track) => track.stop());
    videoStream = null;
  }
  if (videoElement) {
    videoElement.srcObject = null;
  }
  showVideoContainer(false);
  const endButton = document.getElementById('endButton');
  if (endButton) endButton.style.display = 'none';
  serviceErrorNotified = false;
  serviceHealthy = false;
  showLoader(false);
}

function ensureCanvas() {
  if (!videoElement) {
    videoElement = document.getElementById('video');
  }
  if (!videoContainer) {
    videoContainer = document.querySelector('.video-container');
  }
  if (!guideElement) {
    const wrapper = document.querySelector('.video-wrapper');
    if (wrapper) {
      guideElement = document.createElement('div');
      guideElement.className = 'face-guide';
      guideLabel = document.createElement('span');
      guideLabel.textContent = 'Căn chỉnh khuôn mặt';
      guideElement.appendChild(guideLabel);
      wrapper.appendChild(guideElement);
      guideCurrent = null;
      guideTarget = null;
    }
  }
  if (!serviceStatusText) {
    serviceStatusText = document.getElementById('serviceStatusText');
  }
  if (!serviceStatusDot) {
    serviceStatusDot = document.getElementById('serviceStatusDot');
  }
  if (!recognizedListEl) {
    recognizedListEl = document.getElementById('recognizedList');
  }
  if (!recognizedEmptyEl) {
    recognizedEmptyEl = document.getElementById('recognizedEmpty');
  }
  if (!overlayCanvas) {
    overlayCanvas = document.getElementById('overlay');
    overlayCanvas.width = CAPTURE_WIDTH;
    overlayCanvas.height = CAPTURE_HEIGHT;
    overlayCtx = overlayCanvas.getContext('2d');
    overlayCtx.font = '14px sans-serif';
    overlayCtx.textBaseline = 'top';
  }
  if (!captureCanvas) {
    captureCanvas = document.createElement('canvas');
    captureCanvas.width = CAPTURE_WIDTH;
    captureCanvas.height = CAPTURE_HEIGHT;
    captureCtx = captureCanvas.getContext('2d');
  }
}

function showVideoContainer(show) {
  if (!videoContainer) {
    videoContainer = document.querySelector('.video-container');
  }
  if (videoContainer) {
    videoContainer.style.display = show ? 'flex' : 'none';
  }
}

function clearOverlay() {
  if (overlayCtx && overlayCanvas) {
    overlayCtx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
  }
}

function hideGuide() {
  if (guideElement) {
    guideElement.classList.remove('active');
    if (guideLabel) {
      guideLabel.textContent = 'Căn chỉnh khuôn mặt';
    }
  }
  guideTarget = null;
  guideCurrent = null;
}

function updateGuide() {
  if (!guideElement || !guideTarget) return;
  if (!guideCurrent) {
    guideCurrent = { ...guideTarget };
  } else {
    const alpha = 0.18;
    guideCurrent.x = guideCurrent.x + (guideTarget.x - guideCurrent.x) * alpha;
    guideCurrent.y = guideCurrent.y + (guideTarget.y - guideCurrent.y) * alpha;
    guideCurrent.width = guideCurrent.width + (guideTarget.width - guideCurrent.width) * alpha;
    guideCurrent.height = guideCurrent.height + (guideTarget.height - guideCurrent.height) * alpha;
  }
  const centerX = guideCurrent.x + guideCurrent.width / 2;
  const centerY = guideCurrent.y + guideCurrent.height / 2;
  guideElement.style.left = `${centerX}px`;
  guideElement.style.top = `${centerY}px`;
  guideElement.style.width = `${guideCurrent.width}px`;
  guideElement.style.height = `${guideCurrent.height}px`;
  guideElement.style.transform = 'translate(-50%, -50%)';
  guideElement.classList.add('active');
  if (guideHideTimeout) {
    clearTimeout(guideHideTimeout);
    guideHideTimeout = null;
  }
}

function setGuideTargetFromMatch(match) {
  if (!match || !match.bbox) {
    hideGuide();
    return;
  }
  if (!guideElement || !videoElement) return;
  const [x1, y1, x2, y2] = match.bbox;
  const width = Math.max(64, x2 - x1);
  const height = Math.max(72, y2 - y1);
  const centerX = x1 + width / 2;
  const centerY = y1 + height / 2;
  guideTarget = {
    x: centerX - width / 2,
    y: centerY - height / 2,
    width,
    height
  };
  if (guideLabel) {
    guideLabel.textContent = match.label && match.label !== 'unknown' ? match.label : 'Căn chỉnh khuôn mặt';
  }
  updateGuide();
  guideHideTimeout = setTimeout(() => {
    if (!guideTarget) {
      hideGuide();
    }
  }, CAPTURE_INTERVAL_MS * 2);
}

function setServiceStatus(healthy, message) {
  if (!serviceStatusDot || !serviceStatusText) {
    ensureCanvas();
  }
  if (serviceStatusDot) {
    serviceStatusDot.classList.toggle('online', healthy);
    serviceStatusDot.classList.toggle('offline', !healthy);
  }
  if (serviceStatusText && typeof message === 'string') {
    serviceStatusText.textContent = message;
  }
}

function renderRecognitionHistory() {
  if (!recognizedListEl || !recognizedEmptyEl) {
    ensureCanvas();
  }
  if (!recognizedListEl || !recognizedEmptyEl) return;
  recognizedListEl.querySelectorAll('.recognized-item').forEach((node) => node.remove());
  if (!recognizedHistory.length) {
    recognizedEmptyEl.style.display = 'block';
    return;
  }
  recognizedEmptyEl.style.display = 'none';
  const fragment = document.createDocumentFragment();
  recognizedHistory.forEach((item) => {
    const row = document.createElement('div');
    row.className = 'recognized-item';
    const nameSpan = document.createElement('span');
    nameSpan.textContent = item.label;
    const metaSmall = document.createElement('small');
    metaSmall.textContent = `${item.score.toFixed(2)} · ${item.time}`;
    row.appendChild(nameSpan);
    row.appendChild(metaSmall);
    fragment.appendChild(row);
  });
  recognizedListEl.appendChild(fragment);
}

function recordRecognition(label, score) {
  const timestamp = new Date();
  const timeText = timestamp.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
  recognizedHistory = recognizedHistory.filter((entry) => entry.label !== label);
  recognizedHistory.unshift({ label, score, time: timeText });
  if (recognizedHistory.length > MAX_RECOGNIZED_HISTORY) {
    recognizedHistory = recognizedHistory.slice(0, MAX_RECOGNIZED_HISTORY);
  }
  renderRecognitionHistory();
}

async function requestWithTimeout(url, options = {}, timeoutMs = REQUEST_TIMEOUT_MS) {
  if (typeof AbortController === 'undefined') {
    return fetch(url, options);
  }
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs);
  try {
    return await fetch(url, { ...options, signal: controller.signal });
  } finally {
    clearTimeout(timeoutId);
  }
}

async function ensureFaceServiceReady({ forceReload = false } = {}) {
  const now = Date.now();
  if (serviceHealthy && !forceReload && now - lastHealthCheck < HEALTH_CHECK_FRESHNESS_MS) {
    return true;
  }
  try {
    const healthResponse = await requestWithTimeout(`${FACE_SERVICE_URL}/health`, {}, REQUEST_TIMEOUT_MS);
    if (!healthResponse.ok) {
      throw new Error(`Health check failed with status ${healthResponse.status}`);
    }
    serviceHealthy = true;
    serviceErrorNotified = false;
    lastHealthCheck = Date.now();
    if (forceReload) {
      const reloadResponse = await requestWithTimeout(`${FACE_SERVICE_URL}/reload`, {
        method: 'POST'
      }, REQUEST_TIMEOUT_MS * 2);
      if (!reloadResponse.ok) {
        throw new Error(`Reload failed with status ${reloadResponse.status}`);
      }
      setServiceStatus(true, 'Đã nạp lại dữ liệu khuôn mặt. Sẵn sàng nhận diện.');
    } else {
      setServiceStatus(true, 'Dịch vụ nhận diện sẵn sàng.');
    }
    return true;
  } catch (error) {
    console.error('Face service health check failed', error);
    serviceHealthy = false;
    lastHealthCheck = Date.now();
    if (!serviceErrorNotified) {
      showMessage('Không thể kết nối tới dịch vụ nhận diện khuôn mặt. Vui lòng đảm bảo backend đang chạy.');
      serviceErrorNotified = true;
    }
    setServiceStatus(false, 'Không thể kết nối dịch vụ nhận diện. Kiểm tra backend.');
    return false;
  }
}

function captureFrame() {
  if (!videoElement || !captureCtx) return null;
  captureCtx.drawImage(videoElement, 0, 0, CAPTURE_WIDTH, CAPTURE_HEIGHT);
  return captureCanvas.toDataURL('image/jpeg', 0.85);
}

function degradeStability(seenLabels) {
  const toDelete = [];
  stabilityMap.forEach((value, key) => {
    if (!seenLabels.has(key)) {
      const next = value - 1;
      if (next <= 0) toDelete.push(key);
      else stabilityMap.set(key, next);
    }
  });
  toDelete.forEach((key) => stabilityMap.delete(key));
}

function markPresent(label) {
  if (markedOnce.has(label)) return;
  const rows = document.querySelectorAll('#studentTableContainer tr');
  rows.forEach((row, idx) => {
    if (idx === 0) return;
    const registrationNumber = row.cells[0].innerText.trim();
    if (registrationNumber === label) {
      row.cells[5].innerText = 'Có mặt';
      markedOnce.add(label);
    }
  });
  updateCounts();
}

function drawMatches(matches) {
  if (!overlayCtx) return;
  overlayCtx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);
  matches.forEach((match) => {
    const [x1, y1, x2, y2] = match.bbox;
    overlayCtx.strokeStyle = 'lime';
    overlayCtx.lineWidth = 2;
    overlayCtx.strokeRect(x1, y1, x2 - x1, y2 - y1);
    const label = `${match.label} (${match.score.toFixed(2)})`;
    const textWidth = overlayCtx.measureText(label).width;
    overlayCtx.fillStyle = 'rgba(0, 0, 0, 0.6)';
    overlayCtx.fillRect(x1, Math.max(y1 - 20, 0), textWidth + 10, 20);
    overlayCtx.fillStyle = '#fff';
    overlayCtx.fillText(label, x1 + 4, Math.max(y1 - 18, 0));
  });
  if (matches && matches.length) {
    setGuideTargetFromMatch(matches[0]);
  } else {
    hideGuide();
  }
}

function processMatches(matches) {
  const seen = new Set();
  matches.forEach((match) => {
    const label = match.label;
    if (!label || label === 'unknown') return;
    seen.add(label);
    const prev = stabilityMap.get(label) || 0;
    const next = prev + 1;
    stabilityMap.set(label, next);
    if (next >= STABILITY_REQUIRED) {
      recordRecognition(label, match.score || 0);
      markPresent(label);
    }
  });
  degradeStability(seen);
  drawMatches(matches);
}

async function sendFrameToBackend() {
  if (sendingFrame) return;
  const now = Date.now();
  if (!serviceHealthy) {
    if (lastHealthCheck === 0 || now - lastHealthCheck > HEALTH_CHECK_FRESHNESS_MS) {
      const ready = await ensureFaceServiceReady();
      if (!ready) {
        return;
      }
    } else if (serviceErrorNotified) {
      return;
    }
  }
  const frame = captureFrame();
  if (!frame) return;
  sendingFrame = true;
  showLoader(true);
  try {
    const response = await requestWithTimeout(`${FACE_SERVICE_URL}/match`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ image: frame, width: CAPTURE_WIDTH, height: CAPTURE_HEIGHT })
    }, REQUEST_TIMEOUT_MS * 2);
    if (!response.ok) {
      console.error('Face service error', response.status, await response.text());
      serviceHealthy = false;
      if (!serviceErrorNotified) {
        showMessage('Dịch vụ nhận diện khuôn mặt báo lỗi. Kiểm tra lại backend.');
        serviceErrorNotified = true;
      }
      setServiceStatus(false, 'Dịch vụ nhận diện gặp lỗi.');
      clearOverlay();
      return;
    }
    const data = await response.json();
    processMatches(data.matches || []);
    serviceHealthy = true;
    serviceErrorNotified = false;
    setServiceStatus(true, 'Đang nhận diện khuôn mặt...');
  } catch (error) {
    console.error('Error contacting face service', error);
    serviceHealthy = false;
    if (!serviceErrorNotified) {
      showMessage('Mất kết nối với dịch vụ nhận diện khuôn mặt. Đang thử lại...');
      serviceErrorNotified = true;
    }
    setServiceStatus(false, 'Mất kết nối tới dịch vụ nhận diện.');
    clearOverlay();
    hideGuide();
  } finally {
    sendingFrame = false;
    showLoader(false);
  }
}

function sendAttendanceDataToServer() {
  if (sendingFrame) {
    // avoid overlapping network requests
  }
  const attendanceData = [];
  document
    .querySelectorAll('#studentTableContainer tr')
    .forEach((row, index) => {
      if (index === 0) return;
      const studentID = row.cells[0].innerText.trim();
      const course = row.cells[2].innerText.trim();
      const unit = row.cells[3].innerText.trim();
      const attendanceStatus = row.cells[5].innerText.trim();

      attendanceData.push({ studentID, course, unit, attendanceStatus });
    });

  if (attendanceData.length === 0) {
    showMessage('Không có dữ liệu điểm danh để gửi.');
    return;
  }

  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'handle_attendance', true);
  xhr.setRequestHeader('Content-Type', 'application/json');
  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        try {
          const response = JSON.parse(xhr.responseText);
          if (response.status === 'success') {
            showMessage(response.message || 'Lưu điểm danh thành công.');
          } else {
            showMessage(response.message || 'Có lỗi khi lưu điểm danh.');
          }
        } catch (e) {
          showMessage('Error: Failed to parse the response from the server.');
          console.error(e);
        }
      } else {
        showMessage('Lỗi: Không thể lưu điểm danh. HTTP Status: ' + xhr.status);
        console.error('HTTP Error', xhr.status, xhr.statusText);
      }
    }
  };
  xhr.onerror = function () {
    showMessage('Lỗi mạng khi gửi dữ liệu điểm danh.');
  };
  xhr.send(JSON.stringify(attendanceData));
}

function updateTable() {
  const selectedCourseID = document.getElementById('courseSelect').value;
  const selectedUnitCode = document.getElementById('unitSelect').value;
  const selectedVenue = document.getElementById('venueSelect').value;

  const startButton = document.getElementById('startButton');
  if (startButton) startButton.disabled = true;

  stopWebcam();
  resetMarked();

  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'resources/pages/lecture/manageFolder.php', true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      try {
        const response = JSON.parse(xhr.responseText);
        if (response.status === 'success') {
          document.getElementById('studentTableContainer').innerHTML = response.html || '';
          updateCounts();
          if (startButton) {
            startButton.disabled = (response.data || []).length === 0;
          }
          if ((response.data || []).length === 0 && response.message) {
            showMessage('Không có sinh viên trong lựa chọn này.');
          }
        } else {
          showMessage(response.message || 'Không thể tải danh sách sinh viên.');
        }
      } catch (error) {
        console.error('Failed to parse response', error);
        showMessage('Phản hồi không hợp lệ từ máy chủ khi tải danh sách sinh viên.');
      }
    }
  };
  xhr.send(
    'courseID=' +
      encodeURIComponent(selectedCourseID) +
      '&unitID=' +
      encodeURIComponent(selectedUnitCode) +
      '&venueID=' +
      encodeURIComponent(selectedVenue)
  );
}

function setupEventHandlers() {
  const startButton = document.getElementById('startButton');
  const endButton = document.getElementById('endButton');
  if (startButton) {
    startButton.addEventListener('click', async () => {
      ensureCanvas();
      showLoader(true);
      const ready = await ensureFaceServiceReady({ forceReload: true });
      if (!ready) {
        showLoader(false);
        return;
      }
      try {
        await startWebcam();
      } catch (e) {
        showLoader(false);
        return;
      }
      showLoader(false);
      showVideoContainer(true);
      if (endButton) endButton.style.display = 'inline-block';
      if (captureIntervalId) clearInterval(captureIntervalId);
      serviceErrorNotified = false;
      setServiceStatus(true, 'Đang nhận diện khuôn mặt...');
      captureIntervalId = setInterval(sendFrameToBackend, CAPTURE_INTERVAL_MS);
      sendFrameToBackend();
    });
  }
  if (endButton) {
    endButton.addEventListener('click', () => {
      stopWebcam();
    });
  }
}

function showMessage(message) {
  const messageDiv = document.getElementById('messageDiv');
  if (!messageDiv) {
    console.log(message);
    return;
  }
  messageDiv.style.display = 'block';
  messageDiv.innerHTML = message;
  messageDiv.style.opacity = 1;
  setTimeout(() => {
    messageDiv.style.opacity = 0;
  }, 5000);
}

document.getElementById('endAttendance').addEventListener('click', function () {
  document.querySelectorAll('#studentTableContainer tr').forEach((row, index) => {
    if (index === 0) return;
    const statusCell = row.cells[5];
    if (!statusCell) return;
    if (statusCell.innerText.trim().toLowerCase() === 'có mặt') {
      statusCell.innerText = 'present';
    }
  });
  sendAttendanceDataToServer();
  stopWebcam();
  hideGuide();
});

// Initial setup
(function init() {
  ensureCanvas();
  setupEventHandlers();
})();
