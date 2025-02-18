from flask import Flask, request, jsonify
from flask_cors import CORS
import requests
import time
import math
import json
import random
from datetime import datetime as dt
import multiprocessing
import numpy as np
from threading import Thread
import robin_stocks.robinhood as rh
from pushbullet import Pushbullet
import pytz
import sys
sys.setrecursionlimit(5000)
import os
import mysql.connector
import bcrypt
import pyotp
from cryptography.fernet import Fernet
# from robin_stocks.robinhood.crypto import *
# from robin_stocks.robinhood.helper import *
# from robin_stocks.robinhood.profiles import *
# from robin_stocks.robinhood.stocks import *
# from robin_stocks.robinhood.urls import *



app = Flask(__name__)
CORS(app)


@app.route('/bubbles_script', methods=['POST'])
def run_script():

    time.sleep(6)
    with open('./bubblesAuto/data/bubblesReplaySession.json', 'r') as file:
        bubblesCurrentSession = file.read()

    bubblesCurrentSession2 = json.loads(bubblesCurrentSession)
    bubblesCurrentSession3 = [bubblesCurrentSession2[0]]
    with open('./data/bubblesReplaySession.json', 'w') as file:
        file.write(json.dumps(bubblesCurrentSession3))
    return json.dumps(bubblesCurrentSession3)

# If the user clicks the button to replay the session, if will just send back the JSON(JSON2) file location of all the Bubbles stock updates that the script has been adding to everytime it updated the stocks
@app.route('/replaybubblessession', methods=['POST'])
def replayingBubblesSession():
    # with open('./data/bubblesReplaySession.json', 'r') as file:
    #     bubblesReplaySession = file.read()
    with open('./bubblesAuto/data/bubblesReplaySession.json', 'r') as file:
        bubblesReplaySession = file.read()
    return bubblesReplaySession

@app.route('/bubbles_panelsliveupdate', methods=['POST'])
def run_bubbles_panelsliveupdate():
    API_KEY = 'FF3L2jIzaz621a5yNwdsA7FWhkRZcO3z'
    data = request.get_json()
    stock_name = data['stockName']
    # Get the current date
    current_date = dt.now()
    formatted_date = current_date.strftime('%Y-%m-%d')
    from_date = formatted_date
    to_date = formatted_date
    endpoint = f"https://api.polygon.io/v2/aggs/ticker/{stock_name}/range/1/minute/{from_date}/{to_date}?apiKey={API_KEY}"

    response = requests.get(endpoint)
    data = response.json()

    if data['status'] == 'OK':
        price = data['results'][-1]['c']  # Get the closing price from the latest data
        return jsonify({"price": price})
    else:
        return jsonify({"error": "Unable to fetch stock price"}), 500



@app.route('/robinhoodregister', methods=['POST'])
def robinhoodRegister():

    # Use your saved key here
    key = b'CB3i9MmgVbWeIEVp1pxiUo6ozzoRpDusZynPtbxsUJg='
    cipher_suite = Fernet(key)

    data = request.get_json()
    RBEmail = data['RBEmail']
    RBPassword = data['RBPassword']
    RBTotp = data['RBTotp']
    bubblesUsername = data['bubblesUsername']

    # Encrypt the password
    encrypted_password = cipher_suite.encrypt(RBPassword.encode())

    # Create database connection
    conn = mysql.connector.connect(
        host='localhost',
        user='root',
        password='OkrthSNaK5qk',
        database='bubbles'
    )

    try:
        cursor = conn.cursor()

        # Insert or update the users table
        sql = """
        UPDATE users
        SET rhemail = %s, rhpass = %s, totp = %s
        WHERE username = %s
        """
        cursor.execute(sql, (RBEmail, encrypted_password, RBTotp, bubblesUsername))
        conn.commit()

    finally:
        cursor.close()
        conn.close()

    # # Log in to Robinhood
    # rh.login(username=RBEmail, password=RBPassword, store_session=True)
    
    return 'LoggedIn'


def robinhoodLogin(bubblesUsername):

    # Define encryption key and cipher suite
    encryption_key = b'CB3i9MmgVbWeIEVp1pxiUo6ozzoRpDusZynPtbxsUJg='
    cipher_suite = Fernet(encryption_key)
    
    RBEmail = ""
    RBPass = ""
    RBTotp = ""
    # Create database connection
    conn = mysql.connector.connect(
        host='localhost',
        user='root',
        password='OkrthSNaK5qk',
        database='bubbles'
    )
    
    try:
        cursor = conn.cursor()

        # Retrieve the user details
        sql = """
        SELECT rhemail, rhpass, totp
        FROM users
        WHERE username = %s
        """
        cursor.execute(sql, (bubblesUsername,))
        result = cursor.fetchone()
        
        if result:
            RBEmail, RBPass, RBTotp = result
            # Decrypt the password
            RBPass = cipher_suite.decrypt(RBPass.encode()).decode()
            
            # return jsonify({
            #     'RBEmail': RBEmail,
            #     'RBPassword': decrypted_password,
            #     'RBTotp': RBTotp
            # })

    finally:
        cursor.close()
        conn.close()
    
    totp  = pyotp.TOTP(RBTotp).now()
    print("Current OTP:", totp)
    rh.authentication.login(username=RBEmail, password=RBPass, mfa_code=totp)
    print("Logged In *thumbs up, probably, i hope, this could be a bad thing, who knows. I was adopted.")


@app.route('/buystock', methods=['POST'])
def buyStock():

    data = request.get_json()
    print(data)
    stockName = data['stockName']
    shareQuantity = math.floor(float(data['shareQuantity']))
    bubblesUsername = data['bubblesUsername']

    # Log in to Robinhood
    robinhoodLogin(bubblesUsername)

    orderInfoId = rh.orders.order_buy_market(stockName, shareQuantity)
    # print(f"FIRST 2342 {orderInfoId}")
    # time.sleep(105)
    # print(f"SEcond 4220 {orderInfoId}")
    orderInfoId = orderInfoId['id']
    # if bubblesUsername == "Donald Cottman":
    #     time.sleep(2)
    #     robinhoodLogin("Jayden Long")
    #     orderInfoId = rh.orders.order_buy_market(stockName, shareQuantity)
    #     print(orderInfoId)

    # checkQueueBeforeSellTrigger_loopCount = 0
    # def checkQueueBeforeSellTrigger():
    #     nonlocal checkQueueBeforeSellTrigger_loopCount
    #     if checkQueueBeforeSellTrigger_loopCount > 25: # if it hasn't executed for 100 seconds
    #         return False
    #     openOrders = rh.orders.get_all_open_stock_orders()
    #     if len(openOrders) < 1:
    #         print("trade executed")
    #         return True
    #     else:
    #         print('arup')
    #         for i in range(len(openOrders)):
    #             print(openOrders[i])
    #             if orderInfoId == openOrders[i]['id']:
    #                 print("nono")
    #                 print(orderInfoId)
    #                 print(openOrders[i]['id'])
    #                 checkQueueBeforeSellTrigger_loopCount += 1
    #                 time.sleep(4)
    #                 return checkQueueBeforeSellTrigger()
    #         print("trade executed!")
    #         return True
    # if not checkQueueBeforeSellTrigger():
    #     rh.orders.cancel_stock_order(orderInfoId)
    #     return "Baaad stock"

    return "SUCCCUSSS"

@app.route('/sellstock', methods=['POST'])
def sellStock():

    data = request.get_json()
    print(data)
    stockName = data['stockName']

    shareQuantity = 0
    if data['shareQuantity'] not in [None, '']:
        shareQuantity = math.floor(float(data['shareQuantity']))

    trailingPercent = 0
    if data['trailingPercent'] not in [None, '']:
        trailingPercent = float(data['trailingPercent'])

    stoplossPrice = 0
    if data['stoplossPrice'] not in [None, '']:
        stoplossPrice = float(data['stoplossPrice'])
        
    stockPrice = 0
    if data['stockPrice'] not in [None, '']:
        stockPrice = float(data['stockPrice'])

    bubblesUsername = data['bubblesUsername']
    print(trailingPercent)
    print(stoplossPrice)

    # Log in to Robinhood
    robinhoodLogin(bubblesUsername)
    
    setTrailing = False
    setStoploss = False
    if isinstance(trailingPercent, (float)):
        if trailingPercent > 0:
            setTrailing = True
            trailingPercent = round(trailingPercent, 0)
            print('trialing order')
    if isinstance(stoplossPrice, (float)):
        if stoplossPrice > 0:
            setStoploss = True
            stoplossPrice = round(stoplossPrice, 4)
            print('stoploss order')
            print(stoplossPrice)
            print("B")

    orderInfoId = ""
    if setTrailing == True:
        orderInfoId = rh.orders.order_sell_trailing_stop(stockName, shareQuantity, trailingPercent, 'percentage')
        print(orderInfoId)
        if bubblesUsername == "Donald Cottman":
            time.sleep(2)
            robinhoodLogin("Jayden Long")
            orderInfoId = rh.orders.order_sell_trailing_stop(stockName, shareQuantity, trailingPercent, 'percentage')
            print(orderInfoId)
    elif setStoploss == True:
        orderInfoId = rh.orders.order_sell_stop_loss(stockName, shareQuantity, stoplossPrice)
        print(orderInfoId)
        if bubblesUsername == "Donald Cottman":
            time.sleep(2)
            robinhoodLogin("Jayden Long")
            orderInfoId = rh.orders.order_sell_trailing_stop(stockName, shareQuantity, trailingPercent, 'percentage')
            print(orderInfoId)
        return "solddd"
    else:
        orderInfoId = rh.orders.order_sell_market(stockName, shareQuantity)
        print(orderInfoId)
        if bubblesUsername == "Donald Cottman":
            time.sleep(2)
            robinhoodLogin("Jayden Long")
            orderInfoId = rh.orders.order_sell_trailing_stop(stockName, shareQuantity, trailingPercent, 'percentage')
            print(orderInfoId)
        return "solddd"
    orderInfoId = orderInfoId['id']
    
    # if setTrailing == True and setStoploss == True:

    #     checkQueueBeforeSellTrigger_loopCount = 0

    #     while checkQueueBeforeSellTrigger_loopCount < 1500:
            
    #         time.sleep(30)
    #         checkQueueBeforeSellTrigger_loopCount += 1
    #         # Define the endpoint URL (Modify the endpoint based on the Polygon.io documentation and your needs)
    #         url = f"https://api.polygon.io/v2/aggs/ticker/{stockName}/prev"

    #         # Define the parameters including your API key
    #         params = {
    #             "unadjusted": "true",
    #             "apiKey": "FF3L2jIzaz621a5yNwdsA7FWhkRZcO3z"
    #         }

    #         # Make the GET request
    #         response = requests.get(url, params=params)

    #         # Check if the request was successful (status code 200)
    #         if response.status_code == 200:
    #             data = response.json()  # Parse the JSON response
    #             try:
    #                 # Extract and return the current stock price
    #                 if data['results'][0]['c']  <= stoplossPrice:
    #                     print(f"The current price of {stockName} is: {data['results'][0]['c']}")
    #                     rh.orders.cancel_stock_order(orderInfoId)
    #                     rh.orders.order_sell_market(stockName, shareQuantity)
    #             except IndexError:
    #                 print("No data available for the given stock symbol.")
    #         else:
    #             print(f"Failed to get data: {response.status_code}")


    return 'Successfully SOLD! Probably, it\'s actually just queued, but maybe it will sell. Good Luck!'


@app.route('/showrobinhoodhistory', methods=['POST'])
def showRobinhoodHistory():

    # Log in to Robinhood
    robinhoodLogin()

    # print(rh.orders.get_all_open_stock_orders(info=None))
    # print("separate")
    # print(rh.orders.get_all_stock_orders())
    # # ids_array = [element["id"] for element in rh.orders.get_all_stock_orders()]
    # # url = orders_url(rh.orders.get_all_stock_orders(info=None)))
    # # data = request_get(url, 'pagination')
    # # return(filter_data(data, info))
    # print('ello')
    # print(rh.orders.get_stock_order_info(ids_array[0]))
    # rh.orders.get_stock_order_info(orderID)

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)


