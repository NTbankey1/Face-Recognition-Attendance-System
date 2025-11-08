# **Face Recognition Attendance System**

A robust system designed to authenticate individuals and record attendance using **facial recognition technology** powered by deep learning. This project simplifies attendance tracking for classrooms, workplaces, or events.

---
## 🚢 Chạy bằng Docker
Xem `DOCKER.md` để dựng nhanh hệ thống bằng Docker Compose.

---

## 🔄 Kiến trúc nhận diện mới (YOLO + ArcFace)

- **Backend FastAPI** (`services/face_backend/`):
  - YOLO (`yolov8n-face.pt`) để phát hiện khuôn mặt.
  - ArcFace (`arcface_r100_v1`) để sinh embedding và so khớp cosine.
  - API chính: `POST /match`, `POST /reload`, `GET /health`.
- **Frontend (Giảng viên)** gửi frame định kỳ tới backend, nhận danh sách nhãn + độ tin cậy, tự đánh dấu "Có mặt" trên bảng.
- Log hoạt động của Admin và Người dùng được lưu lại dưới dạng JSONL trong `resources/logs/`.

### Cài backend nhận diện
```bash
# Tải trọng số YOLO (một lần)
mkdir -p services/face_backend/weights
wget -O services/face_backend/weights/yolov8n-face.pt \
  https://github.com/ultralytics/assets/releases/download/v0.0.0/yolov8n-face.pt

# Cài môi trường & chạy service (port 8001)
cd services/face_backend
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
uvicorn main:app --host 0.0.0.0 --port 8001 --reload
```
> ArcFace ONNX sẽ được InsightFace tự tải lần đầu (nhớ bật mạng).

### Khởi chạy nhanh
```bash
# từ thư mục gốc dự án
./start_face_service.sh   # đảm bảo đã tạo venv & cài deps trước đó
```

Sau khi service chạy, vào giao diện Giảng viên → Điểm danh. Hệ thống sẽ tự gửi frame tới `http://localhost:8001/match`, hiển thị khung YOLO và đánh dấu "Có mặt" khi độ tin cậy ≥ 0.4 trong ≥2 khung hình liên tiếp.

---

## 📋 Features

- Role-based access for **administrators**, **người dùng** (giảng viên).
- Manage courses, units, venues, and attendance records through an intuitive interface.
- Capture và lưu nhiều ảnh/label cho mỗi sinh viên.
- Logging JSONL cho toàn bộ thao tác thêm dữ liệu/điểm danh.
- Đăng nhập nhanh bằng khuôn mặt với FastAPI backend và PHP session.

## 🔐 Face Login Workflow

1. **Chuẩn bị dữ liệu khuôn mặt cho giảng viên/quản trị viên**
   - Mỗi người dùng nên có thư mục trong `resources/labels/` (ví dụ `resources/labels/admin@gmail.com/`).
   - Có thể tái sử dụng `tools/prepare_images.py` để cân bằng ánh sáng và tăng cường dữ liệu.
2. **Chạy backend nhận diện** (xem [Kiến trúc nhận diện mới](#-kiến-trúc-nhận-diện-mới-yolo--arcface)).
3. **Từ màn hình đăng nhập**
   - Chọn đúng loại người dùng (Quản trị viên hoặc Người dùng).
   - Nhấn `Đăng nhập bằng khuôn mặt`, đưa khuôn mặt vào khung hình và chờ hệ thống nhận diện.
4. **Ánh xạ nhãn → tài khoản**
   - Hệ thống tạo bảng `face_login_map` (nếu chưa tồn tại) để lưu quan hệ giữa nhãn và user.
   - Nếu nhãn khớp với email/ID trong `tbladmin` hoặc `tbllecture`, ánh xạ được tạo tự động.
   - Có thể chủ động thêm ánh xạ:
     ```sql
     INSERT INTO face_login_map(label, user_type, user_id)
     VALUES ('admin@gmail.com', 'administrator', 1);
     ```
5. **Tinh chỉnh ngưỡng nhận diện**
   - Biến môi trường `FACE_LOGIN_MIN_SCORE` (mặc định `0.55`) cho phép siết/giảm yêu cầu độ tin cậy.

> Mẹo: nếu thay đổi dữ liệu trong `resources/labels/`, gọi `POST /reload` của dịch vụ FastAPI để nạp lại embedding.

## Project Structure

````
## Project Structure

```plaintext
Face-Recognition-Attendance-System/
├── database/
│   ├── attendance-db.sql         # SQL file to set up the database
│   └── database_connection.php   # Database connection script
├── models/
│   └── face-api-models.js        # JavaScript models for Face API
├── resources/
│   ├── assets/
│   │   ├── css/                  # CSS files
│   │   └── javascript/           # JavaScript files
│   ├── images/                   # Images directory
│   ├── labels/                   # Stored images of registered students
│   ├── lib/
│   │   └── global-functions.php  # Global PHP functions
│   ├── pages/
│   │   ├── admin/                # Admin-specific pages
│   │   ├── lecturer/             # Lecturer-specific pages
│   │   └── login.php             # Login page
├── index.php                     # Main entry point for all pages
├── .htaccess                     # Redirect rules
└── README.md                     # Project documentation


````

## **🚀 Setup Procedure**

Follow these steps to set up and run the project:

### **1. Clone or Download the Repository**

- Clone the repository using Git:
  ```bash
  git clone https://github.com/francis-njenga/Face-Recognition-Attendance-System.git
  ```
  -Download zip file

### **2. Place the Project in the Server Directory**

If you’re using XAMPP, place the project folder inside the `htdocs` directory:

```plaintext
xampp/htdocs/Face-Recognition-Attendance-System
```

Use a simple folder name, as it will be part of the URL (e.g., attendance-system).

### **3. Start XAMPP**

- Open the XAMPP Control Panel.
- Start the **Apache** and **MySQL** services.

### **4. Set Up the Database**

- Visit **phpMyAdmin**.
- Create a new database.

  - Recommended name: `attendance_db` (You can choose any name, but ensure it matches the configuration in your project files).

- Import the SQL file:
- Locate the `attendance-db.sql` file in the `database/` folder of the project.
- Import it into the newly created database.

### **5. Launch the Application**

Visit the application in your browser:

```plaintext
http://localhost/{your-project-folder-name}
```

## 🧑‍💻 User Guide

### 1. Login as Administrator

- **Email**: `admin@gmail.com`
- **Password**: `@admin_`

Once logged in, you can:

- Add students.
- Manage courses, units, and venues.

⚠️ **Important**:

- Ensure to add at least **two students** and capture **five clear images** for each.
- Poor image quality will affect recognition accuracy. You can retake any image by clicking on it.

### 2. Login as Lecturer

- Create a lecturer account via the admin panel or use a pre-existing one.
- 
**Select lecture user type, to be able to login as lecture**

  *if you have issues using this email and password, create your lecture on admin panel*

- **Email**: `mark@gmail.com`
- **Password**: `@mark_`

As a lecturer:

- Select a course, unit, and venue on the home page.
- Launch the **Face Recognition** feature to begin attendance.

### Additional Features for the Lecturer Panel

- You can also export the attendance to an **Excel** sheet.
- Other simple features are available for managing the lecture panel.

📜 License
This project is licensed under the MIT License.

📧 Support
For any issues or inquiries, feel free to reach out via email: [Francis Njenga](mailto:rajeynj@gmail.com).

### Visit My Website

https://www.frankcodes.tech

You can send donations to my PayPal account: rajeynjenga@gmail.com
# Face-Recognition-Attendance-System
# Face-Recognition-Attendance-System
