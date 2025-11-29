#!/usr/bin/env python3
import json

cells = []

# README Cell
cells.append({
    "cell_type": "markdown",
    "metadata": {"slideshow": {"slide_type": "skip"}},
    "source": ["# 📖 README\\n\\n## Cài đặt\\n```bash\\npip install numpy opencv-python matplotlib pandas scikit-learn fastapi uvicorn nest-asyncio openpyxl Pillow requests\\n```\\n\\n## Xuất Slides\\n```bash\\njupyter nbconvert FaceRecognition_Attendance_Presentation.ipynb --to slides --reveal-prefix https://unpkg.com/reveal.js@4.3.1/ --post serve\\n```"]
})

# Title Slide
cells.append({
    "cell_type": "markdown",
    "metadata": {"slideshow": {"slide_type": "slide"}},
    "source": ["# 🎓 Face Recognition Attendance System\\n## Hệ thống Điểm danh Tự động\\n\\n---\\n\\n**Tác giả:** NTbankey1\\n\\n**GitHub:** [github.com/NTbankey1/Face-Recognition-Attendance-System](https://github.com/NTbankey1/Face-Recognition-Attendance-System)"]
})

# Save
notebook = {
    "cells": cells,
    "metadata": {"kernelspec": {"display_name": "Python 3", "language": "python", "name": "python3"}, "language_info": {"name": "python", "version": "3.8.0  }, "celltoolbar": "Slideshow"},
    "nbformat": 4,
    "nbformat_minor": 5
}

with open("test.ipynb", "w") as f:
    json.dump(notebook, f, indent=2)
print("Created test notebook")
