<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-primary"><i class="fas fa-chart-bar me-2"></i>Analisis Emosi Teks</h2>
            <hr>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-body prediction-container">
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
                                <i class="fas fa-search me-2"></i>Analisis Emosi
                            </button>
                        </div>
                    </form>

                    <div class="loading" style="display: none;">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">Menganalisis emosi menggunakan Naive Bayes...</p>
                    </div>

                    <div id="result"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .prediction-container {
        padding: 30px;
        background-color: white;
        border-radius: 20px;
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

    .input-text {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        margin: 15px 0;
        border: 1px solid #e9ecef;
        color: #505c6e;
    }

    .spinner-border {
        width: 3rem;
        height: 3rem;
        color: #4a90e2;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .result-box {
        animation: fadeIn 0.5s ease-out;
    }

    .probability-bar {
        height: 25px;
        background: #e9ecef;
        border-radius: 15px;
        overflow: hidden;
        margin: 8px 0;
    }

    .probability-fill {
        height: 100%;
        background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
        transition: width 0.5s ease-out;
    }

    .probability-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        color: #505c6e;
        font-size: 0.9em;
    }

    .method-explanation {
        background-color: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-top: 20px;
        border-left: 5px solid #4a90e2;
    }

    .confidence-score {
        font-size: 1.2em;
        font-weight: 600;
        color: #4a90e2;
        margin: 10px 0;
    }

    .result-section {
        margin: 20px 0;
        padding: 15px;
        border-radius: 12px;
        background-color: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
</style>

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
                // Sort probabilities in descending order
                const sortedProbabilities = Object.entries(data.probabilities)
                    .sort(([,a], [,b]) => b - a);

                let probabilitiesHTML = '';
                sortedProbabilities.forEach(([emotion, probability]) => {
                    const percentage = (probability * 100).toFixed(2);
                    probabilitiesHTML += `
                        <div class="probability-item">
                            <div class="probability-label">
                                <span>${emotion}</span>
                                <span>${percentage}%</span>
                            </div>
                            <div class="probability-bar">
                                <div class="probability-fill" style="width: ${percentage}%"></div>
                            </div>
                        </div>
                    `;
                });

                result.innerHTML = `
                    <div class="result-box">
                        <div class="text-center">
                            <h4 class="section-title mb-3">Hasil Analisis Naive Bayes</h4>
                            <div class="emotion-badge">${data.label_text}</div>
                            <div class="confidence-score">
                                Tingkat Akurasi: ${(data.confidence * 100).toFixed(2)}%
                            </div>
                        </div>
                        
                        <div class="input-text">
                            <strong>Teks yang Dianalisis:</strong><br>
                            ${data.input_text}
                        </div>

                        <div class="result-section">
                            <h5>Distribusi Probabilitas Emosi:</h5>
                            ${probabilitiesHTML}
                        </div>

                        <div class="method-explanation">
                            <h5>Penjelasan Metode Naive Bayes:</h5>
                            <p>Analisis ini menggunakan algoritma Naive Bayes yang menghitung probabilitas setiap kelas emosi berdasarkan frekuensi kata dalam teks. Semakin tinggi persentase, semakin besar kemungkinan teks tersebut mengandung emosi yang bersangkutan.</p>
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