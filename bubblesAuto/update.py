from flask import Flask, request, jsonify
import requests

app = Flask(__name__)
API_KEY = 'FF3L2jIzaz621a5yNwdsA7FWhkRZcO3z'

@app.route('/bubbles_panelsliveupdate', methods=['POST'])
def get_stock_price():
    stock_name = request.form.get('stockName')
    endpoint = f"https://api.polygon.io/v2/aggs/ticker/{stock_name}/prev?unadjusted=true&apiKey={API_KEY}"

    response = requests.get(endpoint)
    data = response.json()
    
    if data['status'] == 'OK':
        price = data['results'][0]['c']  # Get the closing price from the latest data
        return jsonify({"price": price})
    else:
        return jsonify({"error": "Unable to fetch stock price"}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', debug=True, port=5001)


