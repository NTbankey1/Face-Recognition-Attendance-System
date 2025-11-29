#!/usr/bin/env python3
"""Complete Jupyter Notebook Generator for Face Recognition Presentation"""
import json

def create_complete_notebook():
    cells = []
    
    # ===== SLIDE 0: README Cell =====
    cells.append({
        "cell_type": "markdown",
        "metadata": {"slideshow": {"slide_type": "skip"}},
        "source": [
            "# 📖 README - Hướng dẫn Sử dụng Notebook\n\n",
            "## Cài đặt Dependencies\n\n",
            "```bash\n",
            "pip install -r requirements_presentation.txt\n",
           "```\n\n",
            "Content của `requirements_presentation.txt`:\n",
            "```\n",
            "numpy>=1.21.0\n",
            "opencv-python>=4.5.0\n",
            "matplotlib>=3.4.0\n",
            "pandas>=1.3.0\n",
            "scikit-learn>=0.24.0\n",
            "fastapi>=0.68.0\n",
            "uvicorn>=0.15.0\n",
            "nest-asyncio>=1.5.0\n",
            "openpyxl>=3.0.0\n",
            "Pillow>=8.3.0\n",
            "requests>=2.26.0\n",
            "```\n\n",
            "## Chạy Notebook\n\n",
            "```bash\n",
            "jupyter notebook FaceRecognition_Attendance_Presentation.ipynb\n# hoặc\njupyter lab FaceRecognition_Attendance_Presentation.ipynb\n",
            "```\n\n",
            "## Xuất sang Reveal.js Slides\n\n",
            "### Phương pháp 1: nbconvert (Recommended)\n\n",
            "```bash\n",
            "jupyter nbconvert FaceRecognition_Attendance_Presentation.ipynb \\\n",
            "  --to slides \\\n",
            "  --reveal-prefix https://unpkg.com/reveal.js@4.3.1/ \\\n",
            "  --post serve\n",
            "```\n\n",
            "### Phương pháp 2: RISE (Interactive trong Jupyter)\n\n",
            "```bash\n",
            "pip install RISE\n",
            "# Sau đó mở notebook và nhấn Alt+R để bắt đầu slideshow\n",
            "```\n\n",
            "## Ghi chú\n",
            "- Notebook chạy trên CPU only, không cần GPU\n",
            "- Sử dụng simulation và synthetic data để demo\n",
            "- Các model thực (YOLOv8,  ArcFace) được thay bằng lightweight alternatives\n"
        ]
    })
    
    # ===== SLIDE 1: Title Slide =====
    cells.append({
        "cell_type": "markdown",
        "metadata": {"slideshow": {"slide_type": "slide"}},
        "source": [
            "# 🎓 Face Recognition Attendance System\n\n",
            "## Hệ thống Điểm danh Tự động bằng Nhận diện Khuôn mặt\n\n",
            "---\n\n",
            "**Tác giả:** NTbankey1\n\n",
            "**Ngày:** November 2025\n\n",
            "**GitHub:** [github.com/NTbankey1/Face-Recognition-Attendance-System](https://github.com/NTbankey1/Face-Recognition-Attendance-System)\n\n",
            "---\n\n",
            "> **TL;DR:** Hệ thống AI tự động điểm danh, tiết kiệm 90% thời gian với độ chính xác 95%+\n\n",
            "<!-- Speaker notes:\n",
            "- Chào mừng các bạn đến với presentation về hệ thống điểm danh tự động\n",
            "- Ứng dụng AI/Deep Learning để giải quyết bài toán điểm danh\n",
            "- Source code hoàn toàn mở trên GitHub\n",
            "-->\n"
        ]
    })
    
    return {"cells": cells, "nbformat": 4, "nbformat_minor": 5, "metadata":  {
        "kernelspec": {"display_name": "Python 3", "language": "python", "name": "python3"},
        "language_info": {"name": "python", "version": "3.8.0", "mimetype": "text/x-python"},
        "celltoolbar": "Slideshow"
    }}

# Create and save
nb = create_complete_notebook()
with open("FaceRecognition_Attendance_Presentation.ipynb", "w", encoding="utf-8") as f:
    json.dump(nb, f, indent=2, ensure_ascii=False)
print(f"✅ Created notebook with {len(nb['cells'])} cells")
