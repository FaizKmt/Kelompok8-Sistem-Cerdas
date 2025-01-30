import requests
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.pipeline import Pipeline
import pickle
import json
from datasets import load_dataset

def fetch_dataset():
    url = "https://datasets-server.huggingface.co/rows"
    params = {
        "dataset": "elvanromp/emosi",
        "config": "default",
        "split": "train",
        "offset": 0,
        "length": 100
    }
    
    try:
        print("Fetching dataset from Hugging Face API...")
        response = requests.get(url, params=params)
        response.raise_for_status()
        data = response.json()
        return data['rows']
    except Exception as e:
        print(f"Error fetching dataset: {e}")
        return None

def create_model():
    try:
        # Alternative method using datasets library
        print("Loading dataset using datasets library...")
        ds = load_dataset("elvanromp/emosi")
        train_data = ds['train']

        print("Processing dataset...")
        texts = train_data['text']
        labels = train_data['label']

        # Convert label strings to numbers
        unique_labels = set(labels)
        label_to_id = {label: idx for idx, label in enumerate(unique_labels)}
        
        print(f"Found labels: {label_to_id}")
        
        # Convert labels to numeric
        numeric_labels = [label_to_id[label] for label in labels]

        # Create and train model
        print("Training model...")
        model = Pipeline([
            ('tfidf', TfidfVectorizer(max_features=5000)),
            ('clf', MultinomialNB()),
        ])

        model.fit(texts, numeric_labels)

        # Save model and label mapping
        print("Saving model and label mapping...")
        model_data = {
            'model': model,
            'label_to_id': label_to_id,
            'id_to_label': {v: k for k, v in label_to_id.items()}
        }
        
        with open('emotion_model.pkl', 'wb') as f:
            pickle.dump(model_data, f)
        
        print("Model saved successfully!")
        return True

    except Exception as e:
        print(f"An error occurred: {str(e)}")
        import traceback
        traceback.print_exc()
        return False

if __name__ == "__main__":
    create_model()