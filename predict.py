from flask import Flask, request, jsonify
from flask_cors import CORS
import numpy as np
from sklearn.naive_bayes import MultinomialNB
from sklearn.feature_extraction.text import TfidfVectorizer
from datasets import load_dataset
import pickle
import os
import re
from nltk.tokenize import word_tokenize
from nltk.corpus import stopwords
from nltk.stem import WordNetLemmatizer
import nltk
from collections import defaultdict

# Download semua resource NLTK yang diperlukan
def download_nltk_resources():
    try:
        print("Downloading NLTK resources...")
        resources = [
            'punkt',
            'stopwords',
            'wordnet',
            'averaged_perceptron_tagger',
            'omw-1.4'  # Open Multilingual Wordnet
        ]
        for resource in resources:
            try:
                nltk.download(resource, quiet=True)
                print(f"Successfully downloaded {resource}")
            except Exception as e:
                print(f"Error downloading {resource}: {str(e)}")
    except Exception as e:
        print(f"Error in download_nltk_resources: {str(e)}")

# Download NLTK resources at startup
download_nltk_resources()

app = Flask(__name__)
CORS(app, resources={r"/*": {"origins": "*"}})

# Global variables
model = None
vectorizer = None
id_to_label = None
emotion_labels = {}
adaptive_classifier = None
word_emotion_dict = defaultdict(lambda: defaultdict(float))

class AdaptiveEmotionClassifier:
    def __init__(self):
        self.lemmatizer = WordNetLemmatizer()
        # Menggunakan stopwords bahasa Indonesia dan Inggris
        self.stop_words = set(stopwords.words('english')).union(set(stopwords.words('indonesian')))
        self.word_emotion_weights = defaultdict(lambda: defaultdict(float))
        self.emotion_words = defaultdict(set)
        
    def preprocess_text(self, text):
        """Preprocess text with advanced cleaning and normalization"""
        try:
            # Lowercase
            text = text.lower()
            # Remove special characters
            text = re.sub(r'[^\w\s]', '', text)
            # Tokenize
            tokens = word_tokenize(text)
            # Remove stopwords and lemmatize
            tokens = [self.lemmatizer.lemmatize(token) for token in tokens 
                     if token not in self.stop_words]
            return tokens
        except Exception as e:
            print(f"Error in preprocess_text: {str(e)}")
            return text.lower().split()  # Fallback to simple tokenization

    def update_emotion_dictionary(self, text, emotion, confidence):
        """Update emotion dictionary with new words and their associations"""
        tokens = self.preprocess_text(text)
        
        # Update word-emotion associations
        for token in tokens:
            self.word_emotion_weights[token][emotion] += confidence
            self.emotion_words[emotion].add(token)
            
        # Normalize weights
        for token in tokens:
            total_weight = sum(self.word_emotion_weights[token].values())
            if total_weight > 0:
                for emotion in self.word_emotion_weights[token]:
                    self.word_emotion_weights[token][emotion] /= total_weight

    def get_emotion_scores(self, text):
        """Get emotion scores for text based on learned associations"""
        tokens = self.preprocess_text(text)
        scores = defaultdict(float)
        
        for token in tokens:
            for emotion, weight in self.word_emotion_weights[token].items():
                scores[emotion] += weight
                
        # Normalize scores
        total_score = sum(scores.values())
        if total_score > 0:
            for emotion in scores:
                scores[emotion] /= total_score
                
        return dict(scores)

def train_naive_bayes():
    """Train Naive Bayes model using the dataset with adaptive learning"""
    global model, vectorizer, id_to_label, adaptive_classifier
    try:
        print("Loading dataset...")
        ds = load_dataset("elvanromp/emosi", split='train')
        
        # Prepare data
        texts = [item['text'] for item in ds]
        labels = [item['label'] for item in ds]
        
        # Create label mapping
        unique_labels = list(set(labels))
        id_to_label = {i: label for i, label in enumerate(unique_labels)}
        label_to_id = {label: i for i, label in id_to_label.items()}
        
        # Convert labels to numeric
        numeric_labels = [label_to_id[label] for label in labels]
        
        # Initialize and train adaptive classifier
        print("Initializing adaptive classifier...")
        adaptive_classifier = AdaptiveEmotionClassifier()
        
        # Create and fit TF-IDF vectorizer
        print("Creating TF-IDF vectorizer...")
        vectorizer = TfidfVectorizer(max_features=5000)
        X = vectorizer.fit_transform(texts)
        
        # Train Naive Bayes model
        print("Training Naive Bayes model...")
        model = MultinomialNB()
        model.fit(X, numeric_labels)
        
        # Train adaptive classifier with dataset
        print("Training adaptive classifier...")
        for text, label in zip(texts, labels):
            adaptive_classifier.update_emotion_dictionary(text, label, 1.0)
        
        # Save models
        print("Saving models...")
        try:
            if os.path.exists('emotion_model.pkl'):
                os.remove('emotion_model.pkl')
            
            with open('emotion_model.pkl', 'wb') as f:
                pickle.dump({
                    'model': model,
                    'vectorizer': vectorizer,
                    'id_to_label': id_to_label,
                    'adaptive_classifier': adaptive_classifier
                }, f)
            print("Models saved successfully!")
        except Exception as e:
            print(f"Error saving models: {e}")
        
        return True
    except Exception as e:
        print(f"Error training models: {e}")
        return False

def load_emotion_labels():
    """Load emotion labels dari dataset"""
    global emotion_labels
    try:
        print("Loading emotion labels from dataset...")
        ds = load_dataset("elvanromp/emosi", split='train')
        
        # Mengumpulkan unique labels dan contoh teks
        unique_emotions = {}
        for item in ds:
            label = item['label']
            if label not in unique_emotions:
                unique_emotions[label] = {
                    'text_example': item['text'],
                    'label_text': item['label_text']
                }
        
        emotion_labels = unique_emotions
        print(f"Loaded {len(emotion_labels)} emotion labels")
        return True
    except Exception as e:
        print(f"Error loading emotion labels: {e}")
        return False

def initialize_model():
    """Initialize model from file or train if not exists"""
    global model, vectorizer, id_to_label, adaptive_classifier
    try:
        if os.path.exists('emotion_model.pkl'):
            print("Loading existing model...")
            try:
                with open('emotion_model.pkl', 'rb') as f:
                    data = pickle.load(f)
                    if not all(k in data for k in ['model', 'vectorizer', 'id_to_label', 'adaptive_classifier']):
                        raise ValueError("Invalid model file format")
                    model = data['model']
                    vectorizer = data['vectorizer']
                    id_to_label = data['id_to_label']
                    adaptive_classifier = data['adaptive_classifier']
                print("Model loaded successfully!")
                return True
            except Exception as e:
                print(f"Error loading existing model: {e}")
                print("Training new model instead...")
                return train_naive_bayes()
        else:
            print("No existing model found. Training new model...")
            return train_naive_bayes()
    except Exception as e:
        print(f"Error initializing model: {e}")
        return False

@app.route('/')
def home():
    return jsonify({
        "status": "success",
        "message": "Server is running",
        "available_emotions": list(emotion_labels.keys())
    })

@app.route('/api/predict', methods=['POST'])
def predict():
    try:
        # Check if models are initialized
        if not all([model, vectorizer, id_to_label, adaptive_classifier]):
            return jsonify({
                'status': 'error',
                'error': 'Models not properly initialized'
            }), 500

        text = request.form.get('text')
        if not text:
            return jsonify({
                'status': 'error',
                'error': 'No text provided'
            }), 400

        try:
            # Get Naive Bayes prediction
            X = vectorizer.transform([text])
            proba = model.predict_proba(X)[0]
            predicted_class = model.predict(X)[0]
            emotion = id_to_label[predicted_class]
            
            # Get adaptive classifier prediction
            adaptive_scores = adaptive_classifier.get_emotion_scores(text)
            
            # Combine predictions (weighted average)
            combined_scores = {}
            for i, (emotion_name, prob) in enumerate(zip(id_to_label.values(), proba)):
                nb_weight = 0.7  # Weight for Naive Bayes
                adaptive_weight = 0.3  # Weight for adaptive classifier
                combined_scores[emotion_name] = (
                    nb_weight * prob + 
                    adaptive_weight * adaptive_scores.get(emotion_name, 0)
                )
            
            # Get final prediction
            final_emotion = max(combined_scores.items(), key=lambda x: x[1])[0]
            confidence = combined_scores[final_emotion]
            
            # Update adaptive classifier with new prediction
            adaptive_classifier.update_emotion_dictionary(text, final_emotion, confidence)
            
            # Save updated model
            try:
                with open('emotion_model.pkl', 'wb') as f:
                    pickle.dump({
                        'model': model,
                        'vectorizer': vectorizer,
                        'id_to_label': id_to_label,
                        'adaptive_classifier': adaptive_classifier
                    }, f)
            except Exception as e:
                print(f"Warning: Could not save updated model: {e}")

            return jsonify({
                'status': 'success',
                'prediction': final_emotion,
                'label_text': emotion_labels.get(final_emotion, {}).get('label_text', final_emotion),
                'input_text': text,
                'confidence': float(confidence),
                'probabilities': {k: float(v) for k, v in combined_scores.items()},
                'learned_associations': dict(adaptive_classifier.word_emotion_weights)
            })

        except Exception as e:
            print(f"Error during prediction: {e}")
            return jsonify({
                'status': 'error',
                'error': f'Prediction error: {str(e)}'
            }), 500

    except Exception as e:
        print(f"Error in predict route: {e}")
        return jsonify({
            'status': 'error',
            'error': str(e)
        }), 500

@app.route('/test', methods=['GET'])
def test():
    return jsonify({
        "status": "success",
        "message": "Test endpoint working",
        "available_emotions": emotion_labels
    })

if __name__ == '__main__':
    if initialize_model() and load_emotion_labels():
        print("Starting server on http://localhost:8000")
        print("Available emotions:", list(emotion_labels.keys()))
        print("Available endpoints:")
        print("  - GET  /")
        print("  - GET  /test")
        print("  - POST /api/predict")
        app.run(host='0.0.0.0', port=8000, debug=True)
    else:
        print("Failed to initialize server. Check errors above.")
