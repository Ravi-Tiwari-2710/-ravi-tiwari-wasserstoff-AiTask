from flask import Flask, request, jsonify
from RAG_COT import generate_resp

app = Flask(__name__)

@app.route('/ask', methods=['POST'])
def ask():
    data = request.json
    question = data.get('question', '')
    if not question:
        return jsonify({"error": "No question provided"}), 400

    response = generate_resp(question)
    return jsonify({"response": response})

if __name__ == '__main__':
    app.run(debug=True)
