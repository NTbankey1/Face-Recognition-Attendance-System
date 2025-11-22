const FACE_SERVICE_URL = window.FACE_SERVICE_URL || "http://localhost:8001";

async function analyzeQuality(imageDataUrl) {
  try {
    const response = await fetch(`${FACE_SERVICE_URL}/quality`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        image: imageDataUrl,
      }),
    });
    if (!response.ok) {
      throw new Error(`Quality API error: ${response.status}`);
    }
    return await response.json();
  } catch (error) {
    console.error("Quality analysis failed:", error);
    return null;
  }
}

function renderQualityStatus(statusElement, report) {
  if (!statusElement) {
    return;
  }
  statusElement.classList.remove("good", "warn", "bad");
  if (!report) {
    statusElement.classList.add("warn");
    statusElement.textContent = "Không thể đánh giá chất lượng. Kiểm tra dịch vụ.";
    return;
  }
  if (!report.detected) {
    statusElement.classList.add("bad");
    statusElement.textContent = "Không phát hiện khuôn mặt. Hãy tiến gần và chụp lại.";
    return;
  }
  const metrics = report.metrics || {};
  const blur = metrics.blur !== undefined ? metrics.blur.toFixed(0) : "—";
  const brightness =
    metrics.brightness !== undefined ? metrics.brightness.toFixed(0) : "—";
  if (report.strict_ok) {
    statusElement.classList.add("good");
    statusElement.textContent = `Ảnh đạt · Sắc nét ${blur} · Ánh sáng ${brightness}`;
  } else if (report.relaxed_ok) {
    statusElement.classList.add("warn");
    statusElement.textContent = `Tạm được · Sắc nét ${blur} · Ánh sáng ${brightness}. Nên chụp lại rõ hơn.`;
  } else {
    statusElement.classList.add("bad");
    statusElement.textContent = `Chưa đạt · Sắc nét ${blur} · Ánh sáng ${brightness}. ${report.message || "Vui lòng chụp lại."}`;
  }
}

async function handleCapturedImage(targetId, dataUrl) {
  const imgElement = document.getElementById(`${targetId}-captured-image`);
  const hiddenInput = document.getElementById(`${targetId}-captured-image-input`);
  const statusElement = document.getElementById(`${targetId}-quality-status`);

  if (imgElement) {
    imgElement.src = dataUrl;
  }
  if (hiddenInput) {
    hiddenInput.value = dataUrl;
  }
  if (statusElement) {
    statusElement.textContent = "Đang phân tích...";
    const report = await analyzeQuality(dataUrl);
    renderQualityStatus(statusElement, report);
  }
}

//add capture student image
function openCamera(buttonId) {
  navigator.mediaDevices
    .getUserMedia({ video: true })
    .then((stream) => {
      const video = document.createElement("video");
      video.srcObject = stream;
      document.body.appendChild(video);

      video.play();

      setTimeout(async () => {
        const capturedImage = captureImage(video);
        stream.getTracks().forEach((track) => track.stop());
        document.body.removeChild(video);
        await handleCapturedImage(buttonId, capturedImage);
      }, 500);
    })
    .catch((error) => {
      console.error("Error accessing webcam:", error);
    });
}
const takeMultipleImages = async () => {
  document.getElementById("open_camera").style.display = "none";

  const images = document.getElementById("multiple-images");

  for (let i = 1; i <= 5; i++) {
    // Create the image box element
    const imageBox = document.createElement("div");
    imageBox.classList.add("image-box");

    const imgElement = document.createElement("img");
    imgElement.id = `image_${i}-captured-image`;

    const editIcon = document.createElement("div");
    editIcon.classList.add("edit-icon");

    const icon = document.createElement("i");
    icon.classList.add("fas", "fa-camera");
    icon.setAttribute("onclick", `openCamera("image_"+${i})`);

    const hiddenInput = document.createElement("input");
    hiddenInput.type = "hidden";
    hiddenInput.id = `image_${i}-captured-image-input`;
    hiddenInput.name = `capturedImage${i}`;

    editIcon.appendChild(icon);
    imageBox.appendChild(imgElement);
    imageBox.appendChild(editIcon);
    imageBox.appendChild(hiddenInput);
    const statusElement = document.createElement("div");
    statusElement.classList.add("quality-status");
    statusElement.id = `image_${i}-quality-status`;
    statusElement.textContent = "Chưa chụp";
    imageBox.appendChild(statusElement);
    images.appendChild(imageBox);
    await captureImageWithDelay(i);
  }
};

const captureImageWithDelay = async (i) => {
  try {
    // Get camera stream
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    const video = document.createElement("video");
    video.srcObject = stream;
    document.body.appendChild(video);
    video.play();

    // Wait for 500ms before capturing the image
    await new Promise((resolve) => setTimeout(resolve, 500));

    // Capture the image
    const capturedImage = captureImage(video);

    // Stop the video stream and remove the video element
    stream.getTracks().forEach((track) => track.stop());
    document.body.removeChild(video);

    await handleCapturedImage(`image_${i}`, capturedImage);
  } catch (err) {
    console.error("Error accessing camera: ", err);
  }
};

function captureImage(video) {
  const canvas = document.createElement("canvas");
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  const context = canvas.getContext("2d");

  context.drawImage(video, 0, 0, canvas.width, canvas.height);

  return canvas.toDataURL("image/png");
}

//hide and display form
