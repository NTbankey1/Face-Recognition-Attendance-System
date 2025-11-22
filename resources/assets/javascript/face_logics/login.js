(function () {
  const triggerButton = document.getElementById('faceLoginButton');
  if (!triggerButton) {
    return;
  }

  const inlineMessage = document.getElementById('faceLoginInlineMessage');
  const modal = document.getElementById('faceLoginModal');
  const closeButton = document.getElementById('faceLoginClose');
  const cancelButton = document.getElementById('faceLoginCancel');
  const retryButton = document.getElementById('faceLoginRetry');
  const statusText = document.getElementById('faceLoginStatusText');
  const loader = document.getElementById('faceLoginLoader');
  const video = document.getElementById('faceLoginVideo');
  const toast = document.getElementById('faceLoginToast');
  const userTypeSelect = document.getElementById('userTypeSelect');

  const captureCanvas = document.createElement('canvas');
  const captureCtx = captureCanvas.getContext('2d', { willReadFrequently: true });

  const CAPTURE_INTERVAL_MS = 1300;
  const INITIAL_DELAY_MS = 400;
  const MAX_ATTEMPTS = 12;
  const HIDDEN_CLASS = 'is-hidden';

  const allowedRoles = ['administrator', 'lecture'];

  let isActive = false;
  let videoStream = null;
  let captureTimer = null;
  let sendingFrame = false;
  let attempts = 0;

  function supportsFaceLogin() {
    return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && captureCtx);
  }

  function setInlineMessage(message, type = 'info') {
    if (!inlineMessage) return;
    inlineMessage.textContent = message || '';
    inlineMessage.dataset.state = type;
    inlineMessage.hidden = !message;
  }

  if (!supportsFaceLogin()) {
    triggerButton.disabled = true;
    triggerButton.classList.add('disabled');
    setInlineMessage('Trình duyệt không hỗ trợ đăng nhập bằng khuôn mặt. Vui lòng sử dụng mật khẩu.', 'error');
    return;
  }

  function showModal() {
    if (!modal) return;
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('face-login-open');
  }

  function hideModal() {
    if (!modal) return;
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('face-login-open');
  }

  function updateStatus(message, { tone = 'info' } = {}) {
    if (statusText) {
      statusText.textContent = message;
      statusText.dataset.state = tone;
    }
  }

  function showLoader(show) {
    if (!loader) return;
    loader.hidden = !show;
  }

  function stopVideoStream() {
    if (captureTimer) {
      clearTimeout(captureTimer);
      captureTimer = null;
    }
    if (videoStream) {
      videoStream.getTracks().forEach((track) => track.stop());
    }
    videoStream = null;
    if (video) {
      video.srcObject = null;
    }
  }

  function resetState() {
    attempts = 0;
    sendingFrame = false;
    if (retryButton) {
      retryButton.hidden = true;
    }
    showLoader(false);
  }

  async function initCamera() {
    try {
      showLoader(true);
      updateStatus('Đang yêu cầu quyền truy cập camera...', { tone: 'info' });
      videoStream = await navigator.mediaDevices.getUserMedia({
        video: {
          width: { ideal: 640 },
          height: { ideal: 480 },
          facingMode: 'user'
        },
        audio: false
      });
      if (!video) return;
      video.srcObject = videoStream;
      await video.play();
      await new Promise((resolve) => {
        if (video.readyState >= 2) {
          resolve();
          return;
        }
        video.addEventListener('loadedmetadata', resolve, { once: true });
      });
      captureCanvas.width = video.videoWidth || 640;
      captureCanvas.height = video.videoHeight || 480;
      showLoader(false);
      updateStatus('Đang tìm khuôn mặt...', { tone: 'info' });
      scheduleCapture(INITIAL_DELAY_MS);
    } catch (error) {
      console.error('Face login camera error', error);
      showLoader(false);
      updateStatus('Không thể truy cập camera. Kiểm tra quyền truy cập và thử lại.', { tone: 'error' });
      if (retryButton) retryButton.hidden = false;
    }
  }

  function scheduleCapture(delay = CAPTURE_INTERVAL_MS) {
    if (!isActive) return;
    if (captureTimer) {
      clearTimeout(captureTimer);
    }
    captureTimer = setTimeout(sendFrameToBackend, delay);
  }

  function getSelectedRole() {
    if (!userTypeSelect) return null;
    const value = (userTypeSelect.value || '').trim();
    return allowedRoles.includes(value) ? value : null;
  }

  async function sendFrameToBackend() {
    if (!isActive || sendingFrame) {
      scheduleCapture();
      return;
    }
    if (!video || !videoStream) {
      scheduleCapture();
      return;
    }
    if (!video.videoWidth || !video.videoHeight) {
      scheduleCapture(300);
      return;
    }

    sendingFrame = true;
    attempts += 1;
    showLoader(true);
    updateStatus('Đang nhận diện khuôn mặt...', { tone: 'info' });

    captureCtx.drawImage(video, 0, 0, captureCanvas.width, captureCanvas.height);
    const dataUrl = captureCanvas.toDataURL('image/jpeg', 0.85);
    const payload = {
      image: dataUrl,
      width: captureCanvas.width,
      height: captureCanvas.height
    };
    const role = getSelectedRole();
    if (role) {
      payload.userType = role;
    }

    try {
      const response = await fetch('/api/face-login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      const result = await response.json();
      if (response.ok) {
        updateStatus('Nhận diện thành công! Đang chuyển hướng...', { tone: 'success' });
        showToast(`Xin chào ${result.match?.label || ''}. Đăng nhập thành công.`, 'success');
        setTimeout(() => {
          window.location.href = result.redirect ? `/${result.redirect}` : '/home';
        }, 600);
        return;
      }

      const message = result.message || 'Không thể đăng nhập bằng khuôn mặt.';
      const tone = response.status >= 500 ? 'error' : 'warning';
      updateStatus(message, { tone });
      if (response.status === 401 || response.status === 403 || response.status === 404) {
        if (retryButton) retryButton.hidden = false;
      } else if (attempts >= MAX_ATTEMPTS) {
        if (retryButton) retryButton.hidden = false;
      }
    } catch (error) {
      console.error('Face login network error', error);
      updateStatus('Mất kết nối tới máy chủ. Kiểm tra mạng hoặc backend.', { tone: 'error' });
      if (retryButton) retryButton.hidden = false;
    } finally {
      sendingFrame = false;
      showLoader(false);
      if (isActive && (retryButton?.hidden ?? true)) {
        scheduleCapture();
      }
    }
  }

  function showToast(message, type = 'info') {
    if (!toast) return;
    toast.textContent = message;
    toast.dataset.state = type;
    toast.classList.add('visible');
    setTimeout(() => {
      toast.classList.remove('visible');
    }, 2500);
  }

  function openModal() {
    if (isActive) return;
    isActive = true;
    resetState();
    updateStatus('Đang khởi tạo camera...', { tone: 'info' });
    showModal();
    initCamera();
  }

  function closeModal(reason = '') {
    if (!isActive) return;
    isActive = false;
    stopVideoStream();
    hideModal();
    if (reason === 'cancel') {
      showToast('Đã hủy đăng nhập bằng khuôn mặt.', 'info');
    }
  }

  triggerButton.addEventListener('click', () => {
    setInlineMessage('');
    openModal();
  });

  closeButton?.addEventListener('click', () => {
    closeModal('cancel');
  });

  cancelButton?.addEventListener('click', () => {
    closeModal('cancel');
  });

  retryButton?.addEventListener('click', () => {
    if (!isActive) {
      openModal();
      return;
    }
    attempts = 0;
    retryButton.hidden = true;
    updateStatus('Đang thử lại nhận diện...', { tone: 'info' });
    scheduleCapture(250);
  });

  window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isActive) {
      closeModal('cancel');
    }
  });

  modal?.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeModal('cancel');
    }
  });
})();
