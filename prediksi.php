<!DOCTYPE html>
<html>
<head>
    <title>Prediksi Emosi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }

        .prediction-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .page-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2.2em;
        }

        .form-label {
            color: #34495e;
            font-weight: 500;
            font-size: 1.1em;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 0.2rem rgba(74,144,226,0.25);
        }

        .btn-primary {
            background-color: #4a90e2;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #357abd;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74,144,226,0.3);
        }

        .loading {
            text-align: center;
            padding: 20px;
        }

        .result-box {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .emotion-badge {
            display: inline-block;
            padding: 10px 25px;
            font-size: 1.3em;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            border-radius: 30px;
            margin: 15px 0;
            text-transform: capitalize;
            box-shadow: 0 5px 15px rgba(74,144,226,0.3);
        }

        .section-title {
            color: #2c3e50;
            font-weight: 500;
            margin: 20px 0 10px;
            font-size: 1.2em;
        }

        .example-text {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin: 15px 0;
            font-style: italic;
            border-left: 5px solid #4a90e2;
            color: #505c6e;
        }

        .input-text {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin: 15px 0;
            border: 1px solid #e9ecef;
            color: #505c6e;
        }

        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            margin: 15px 0;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            color: #4a90e2;
        }

        /* Animation for results */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .result-box {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="container prediction-container">
        <h1 class="page-title">Analisis Emosi dari Teks</h1>
        
        <div id="serverStatus" class="alert alert-info mb-4" style="display: none;">
            Mengecek koneksi server...
        </div>
        
        <form id="predictionForm">
            <div class="mb-4">
                <label for="text" class="form-label">Masukkan Teks untuk Dianalisis:</label>
                <textarea 
                    class="form-control" 
                    id="text" 
                    name="text" 
                    rows="4" 
                    placeholder="Tuliskan teks yang ingin Anda analisis emosinya di sini..."
                    required
                ></textarea>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    Analisis Emosi
                </button>
            </div>
        </form>

        <div class="loading" style="display: none;">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Menganalisis emosi...</p>
        </div>

        <div id="result"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const SERVER_URL = 'http://localhost:8000';
        
        async function checkServer() {
            const statusDiv = document.getElementById('serverStatus');
            const submitBtn = document.getElementById('submitBtn');
            
            try {
                statusDiv.style.display = 'block';
                statusDiv.textContent = 'Mengecek koneksi server...';
                
                const response = await fetch(`${SERVER_URL}/`);
                if (response.ok) {
                    statusDiv.className = 'alert alert-success';
                    statusDiv.textContent = 'Server terhubung!';
                    submitBtn.disabled = false;
                    
                    // Fade out success message after 3 seconds
                    setTimeout(() => {
                        statusDiv.style.transition = 'opacity 0.5s ease-out';
                        statusDiv.style.opacity = '0';
                        setTimeout(() => {
                            statusDiv.style.display = 'none';
                        }, 500);
                    }, 3000);
                } else {
                    throw new Error('Server error');
                }
            } catch (error) {
                statusDiv.className = 'alert alert-danger';
                statusDiv.textContent = 'Tidak dapat terhubung ke server. Pastikan server Python berjalan.';
                submitBtn.disabled = true;
            }
        }

        document.addEventListener('DOMContentLoaded', checkServer);

        document.getElementById('predictionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const loading = document.querySelector('.loading');
            const result = document.getElementById('result');
            const submitBtn = document.getElementById('submitBtn');
            
            try {
                loading.style.display = 'block';
                result.innerHTML = '';
                submitBtn.disabled = true;
                
                const formData = new FormData(this);
                
                const response = await fetch(`${SERVER_URL}/api/predict`, {
                    method: 'POST',
                    body: formData,
                    mode: 'cors'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    result.innerHTML = `
                        <div class="result-box">
                            <div class="text-center">
                                <h4 class="section-title mb-3">Hasil Analisis</h4>
                                <div class="emotion-badge">${data.label_text}</div>
                            </div>
                            
                            <div class="input-text">
                                <strong>Teks yang Dianalisis:</strong><br>
                                ${data.input_text}
                            </div>
                        </div>
                    `;
                } else {
                    throw new Error(data.error || 'Terjadi kesalahan dalam analisis');
                }
            } catch (error) {
                console.error('Error:', error);
                result.innerHTML = `
                    <div class="alert alert-danger">
                        ${error.message || 'Tidak dapat terhubung ke server prediksi. Pastikan server Python berjalan.'}
                    </div>
                `;
            } finally {
                loading.style.display = 'none';
                submitBtn.disabled = false;
            }
        });
    </script>
</body>
</html>