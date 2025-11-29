# Face Recognition Attendance System

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Python](https://img.shields.io/badge/Python-3.8+-blue.svg)](https://www.python.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)](https://www.mysql.com/)

A robust **face recognition-based attendance system** powered by deep learning (YOLO + ArcFace) that simplifies attendance tracking for classrooms, workplaces, and events. The system provides real-time face recognition, automatic attendance marking, and comprehensive administrative features.

## 🌟 Features

- 🔐 **Face Recognition Login** - Secure authentication using facial recognition
- 📸 **Real-time Attendance Tracking** - Automatic attendance marking with live camera feed
- 👥 **Role-based Access Control** - Separate interfaces for administrators and lecturers
- 📊 **Comprehensive Management** - Manage students, courses, units, and venues
- 📈 **Reporting & Export** - Export attendance records to Excel format
- 📝 **Activity Logging** - Complete audit trail with JSONL logs
- 🎯 **Quality Control** - Automatic image quality checking during enrollment
- 🐳 **Docker Support** - Easy deployment with Docker Compose

## 📚 Documentation

- **[Hướng dẫn sử dụng chi tiết](HUONG_DAN_SU_DUNG.md)** - Comprehensive guide for Administrators and Lecturers (Vietnamese)
- **[Hướng dẫn Docker](DOCKER.md)** - Docker deployment guide (Vietnamese)
- **[Phân công công việc](PHAN_CONG_CONG_VIEC.md)** - Team task assignment document (Vietnamese)
- **[Tài liệu thuyết trình](PRESENTATION.md)** - Presentation materials (Vietnamese)

## 🏗️ System Architecture

### Technology Stack

**Backend (Face Recognition):**
- **FastAPI** (Python) - RESTful API server
- **YOLOv8n-face** - Face detection model
- **ArcFace (R100)** - Face embedding and matching

**Frontend:**
- **PHP** - Server-side logic
- **JavaScript (ES6+)** - Client-side interactions
- **HTML/CSS** - User interface

**Database:**
- **MySQL** - Data persistence

**Deployment:**
- **Docker & Docker Compose** - Containerization
- **Apache/Nginx** - Web server

### Recognition Pipeline

```
Camera Feed → YOLO Detection → ArcFace Embedding → Cosine Matching → Attendance Update
```

1. **Face Detection**: YOLO detects faces in video frames
2. **Feature Extraction**: ArcFace generates face embeddings
3. **Matching**: Cosine similarity comparison with enrolled faces
4. **Attendance Marking**: Automatic marking when confidence ≥ 0.4 in ≥2 consecutive frames

## 🚀 Quick Start

### Option 1: Docker (Recommended)

The easiest way to get started:

```bash
# Clone the repository
git clone git@github.com:NTbankey1/Face-Recognition-Attendance-System.git
cd Face-Recognition-Attendance-System

# See DOCKER.md for detailed Docker setup instructions
```

### Option 2: Manual Installation

#### Prerequisites

- Python 3.8+
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx web server
- Webcam for face recognition

#### Step 1: Clone Repository

```bash
git clone git@github.com:NTbankey1/Face-Recognition-Attendance-System.git
cd Face-Recognition-Attendance-System
```

#### Step 2: Setup Database

1. Create a MySQL database (e.g., `attendance_db`)
2. Import the database schema:
   ```bash
   mysql -u root -p attendance_db < database/attendance-db.sql
   ```
3. Update database connection in `database/database_connection.php`

#### Step 3: Setup Face Recognition Backend

```bash
# Navigate to backend directory
cd services/face_backend

# Create virtual environment
python -m venv .venv
source .venv/bin/activate  # On Windows: .venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt

# Download YOLO model weights
mkdir -p weights
wget -O weights/yolov8n-face.pt \
  https://github.com/ultralytics/assets/releases/download/v0.0.0/yolov8n-face.pt

# Run the backend service
uvicorn main:app --host 0.0.0.0 --port 8001 --reload
```

> **Note**: ArcFace ONNX model will be automatically downloaded by InsightFace on first run (requires internet connection).

#### Step 4: Setup Web Frontend

1. Copy project to web server directory:
   - **XAMPP**: `xampp/htdocs/Face-Recognition-Attendance-System`
   - **Linux Apache**: `/var/www/html/Face-Recognition-Attendance-System`

2. Start Apache and MySQL services

3. Access the application:
   ```
   http://localhost/Face-Recognition-Attendance-System
   ```

## 📖 Usage Guide

### Default Login Credentials

**Administrator:**
- Email: `admin@gmail.com`
- Password: `@admin_`

**Lecturer (Sample):**
- Email: `mark@gmail.com`
- Password: `@mark_`

> **Important**: For security, change default passwords after first login.

### Administrator Features

1. **Student Management**
   - Add/Edit/Delete students
   - Capture 5 face images per student
   - Automatic image quality validation
   - Face enrollment with augmentation

2. **Course & Unit Management**
   - Create and manage courses
   - Define units and their relationships
   - Assign lecturers to courses

3. **Venue Management**
   - Add venues (classrooms, labs, halls)
   - Upload venue images
   - Set venue descriptions

4. **Lecturer Management**
   - Create lecturer accounts
   - Assign courses to lecturers
   - Manage permissions

### Lecturer Features

1. **Attendance Taking**
   - Select course, unit, and venue
   - Launch face recognition feature
   - Real-time face detection and recognition
   - Automatic attendance marking

2. **View & Export**
   - View attendance records
   - Export to Excel format
   - Filter by date, course, or venue

### Face Login Workflow

1. **Prepare Face Data** (Admin/Lecturer)
   - Capture 5 face images from different angles
   - System validates image quality (blur, brightness)
   - Images stored in `resources/labels/[email]/`

2. **Start Backend Service**
   - Run FastAPI backend on port 8001
   - Backend loads face embeddings on startup

3. **Login**
   - Select user type (Administrator/Lecturer)
   - Click "Face Login" button
   - Position face in camera frame
   - System authenticates automatically

4. **Label Mapping**
   - System creates `face_login_map` table automatically
   - Labels matched to user accounts by email/ID
   - Manual mapping also supported

5. **Configuration**
   - Adjust recognition threshold via `FACE_LOGIN_MIN_SCORE` (default: 0.55)

## 🔧 API Endpoints

### Face Recognition Backend (Port 8001)

**POST `/match`** - Face Recognition
```json
Request:
{
  "image": "base64_encoded_image",
  "user_type": "lecturer" | "administrator" (optional)
}

Response:
{
  "matches": [
    {
      "label": "student_id@example.com",
      "score": 0.85,
      "bbox": [x1, y1, x2, y2]
    }
  ]
}
```

**POST `/quality`** - Image Quality Check
```json
Request:
{
  "image": "base64_encoded_image"
}

Response:
{
  "status": "good" | "acceptable" | "poor",
  "blur_score": 0.75,
  "brightness": 0.6,
  "face_size": 150
}
```

**POST `/reload`** - Reload Face Embeddings
- Reloads all face embeddings from `resources/labels/`
- Useful after adding/updating student faces

**GET `/health`** - Health Check
```json
Response:
{
  "status": "healthy",
  "model_loaded": true,
  "faces_count": 50
}
```

## 📁 Project Structure

```
Face-Recognition-Attendance-System/
├── database/
│   ├── attendance-db.sql           # Database schema
│   ├── database_connection.php     # DB connection config
│   ├── sample_data.sql             # Sample data
│   └── generate_password_hash.php  # Password utility
├── services/
│   ├── face_backend/               # FastAPI face recognition service
│   │   ├── main.py                 # FastAPI application
│   │   ├── requirements.txt        # Python dependencies
│   │   ├── Dockerfile              # Docker config
│   │   └── weights/                # Model weights (gitignored)
│   └── web/                        # Web service
│       ├── Dockerfile
│       └── apache-vhost.conf
├── resources/
│   ├── api/                        # API endpoints
│   │   └── face-login.php
│   ├── assets/                     # Frontend assets
│   │   ├── css/                    # Stylesheets
│   │   └── javascript/             # JavaScript files
│   │       └── face_logics/        # Face recognition JS
│   ├── images/                     # Static images
│   ├── labels/                     # Enrolled face images (gitignored)
│   ├── labels_raw/                 # Raw face images (gitignored)
│   ├── lib/                        # PHP libraries
│   │   └── php_functions.php
│   ├── logs/                       # Activity logs (gitignored)
│   └── pages/                      # PHP pages
│       ├── administrator/          # Admin pages
│       ├── lecture/                # Lecturer pages
│       └── login.php               # Login page
├── models/                         # Face-API JavaScript models
├── tools/                          # Utility scripts
│   ├── prepare_images.py           # Image preprocessing
│   └── test_match.py               # Testing utilities
├── docker-compose.yml              # Docker Compose config
├── index.php                       # Entry point
├── router.php                      # URL router
├── .htaccess                       # Apache rewrite rules
└── README.md                       # This file
```

## ⚙️ Configuration

### Environment Variables

**Backend Service** (`.env` in `services/face_backend/`):
```env
FACE_LOGIN_MIN_SCORE=0.55          # Login recognition threshold
FACE_ATTENDANCE_MIN_SCORE=0.4      # Attendance recognition threshold
FACE_STRICT_ENROLLMENT=1            # Strict quality check for enrollment
PORT=8001                           # Backend port
```

**Frontend** (`database/database_connection.php`):
```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "attendance_db";
```

## 🔒 Security Considerations

- ⚠️ **Change default passwords** immediately after installation
- 🔐 Use strong passwords for database connections
- 🌐 Implement HTTPS in production environments
- 👤 Regular review of activity logs
- 🔄 Keep dependencies updated for security patches

## 📊 Recognition Performance

- **Detection Threshold**: Confidence ≥ 0.4 for attendance
- **Login Threshold**: Confidence ≥ 0.55 for authentication
- **Confirmation**: Requires ≥2 consecutive frame matches
- **Processing Speed**: Real-time processing (~30 FPS capable)

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📜 License

This project is licensed under the **MIT License** - see the LICENSE file for details.

## 👨‍💻 Author

**NTbankey1**

- GitHub: [@NTbankey1](https://github.com/NTbankey1)
- Repository: [Face-Recognition-Attendance-System](https://github.com/NTbankey1/Face-Recognition-Attendance-System)

## 🙏 Acknowledgments

- Original project inspiration: [Francis Njenga](https://github.com/francis-njenga)
- YOLO face detection: [Ultralytics](https://github.com/ultralytics)
- ArcFace implementation: [InsightFace](https://github.com/deepinsight/insightface)

## 📧 Support

For issues, questions, or contributions:
- 📧 Open an issue on [GitHub Issues](https://github.com/NTbankey1/Face-Recognition-Attendance-System/issues)
- 📖 Check the documentation files in the repository

---

**⭐ If you find this project useful, please consider giving it a star!**
