# 🏥 DocEx - AI Medical Report Assistant

> **Democratizing Health Information:** Turn complex medical lab reports into simple, actionable insights using AI.

DocEx is a web application that helps patients understand their medical diagnosis. Users can upload a photo of any lab report (blood test, prescription, etc.), and our AI analyzes the text to provide a summary, identify critical values, and generate a personalized diet plan.

---

## 🚀 Key Features

* **📄 OCR Text Extraction:** Uses Tesseract OCR to read text from scanned images or photos.
* **🧠 AI Analysis:** Powered by **Groq (Llama 3 70B)** to explain medical terms in simple English.
* **💬 Interactive Health Chat:** A built-in chatbot to answer follow-up questions about the report.
* **🥗 Smart Diet Planner:** Generates meal plans based on the specific deficiencies found in the report.
* **🗣️ Voice Support:** Reads the analysis aloud for better accessibility.
* **🔐 Secure Auth:** Firebase Authentication for secure user login.

---

## 🛠️ Tech Stack

* **Frontend:** HTML5, CSS3, JavaScript (Bootstrap 5)
* **Backend:** PHP (Native)
* **Database:** MySQL (XAMPP)
* **AI & ML:** * Groq API (Llama3-70b-8192)
    * Tesseract OCR (Server-side)
* **Authentication:** Firebase Auth

---

## ⚙️ How to Run Locally

### Prerequisites
* **XAMPP** (Apache & MySQL) installed.
* **Tesseract OCR** installed on Windows (`C:\Program Files\Tesseract-OCR\tesseract.exe`).
* **Groq API Key**.

### Installation

1.  **Clone the Repository**
    ```bash
    git clone [https://github.com/Sandeepkumar-6/DocEx2.git](https://github.com/Sandeepkumar-6/DocEx2.git)
    cd DocEx2
    ```

2.  **Setup Database**
    * Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
    * Create a database named `codebuddies_db`.
    * Import the `api/db.sql` file provided in the repo.

3.  **Configure Secrets**
    * Create a file named `secrets.php` inside the `api/` folder.
    * Add your Groq API Key:
        ```php
        <?php
        $groq_secret_key = "gsk_YOUR_REAL_API_KEY_HERE";
        ?>
        ```

4.  **Create Uploads Folder**
    * Create an empty folder named `uploads` in the root directory.

5.  **Run**
    * Start Apache and MySQL in XAMPP.
    * Open your browser and go to: `http://localhost/DocEx2`

---

## 📸 Screenshots

*(You can upload screenshots of your project here later!)*

* **Landing Page**
* **Analysis Result**
* **Doctor Dashboard**

---

## 🔮 Future Scope

* **Mobile App:** Converting the web app to React Native.
* **Hospital Integration:** Direct API link with hospital labs.
* **Multilingual Support:** Translating reports into regional Indian languages.

---

