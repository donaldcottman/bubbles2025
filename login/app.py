from flask import Flask, request
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

from robin_stocks.robinhood.crypto import *
from robin_stocks.robinhood.helper import *
from robin_stocks.robinhood.profiles import *
from robin_stocks.robinhood.stocks import *
from robin_stocks.robinhood.urls import *



app = Flask(__name__)
CORS(app)

topStockData = "null"
stockBlackList = []
# (Trying turning on the bot at 6:30)
startBotAt = 635 # Time of day to start, in 00:00 to 23:59 form without the colon
# stopBotAt = 705 # Time of day to stop, in 00:00 to 23:59 form without the colon
stopBotAt = 810 # Time of day to stop, in 00:00 to 23:59 form without the colon
startBuyingAt = 641
maxBudget = 1600 # Total Budget
budget = 400 # Budget for each stock
tradesMade = 0


pb = Pushbullet("o.cJrqEoYYM3JgU7Wfgl5vccNCi7cwuyky")
pb.push_note("Here", f"present1")
def robinhoodLogin():

    # Log in to Robinhood
    rh.login(username='donaldcottman@gmail.com', password='Bubbles2024$', store_session=True)
    #rh.login(username='theironarmorgames@gmail.com', password='fomGk6&%1#UY', store_session=True)
    print("Logged In *thumbs up, probably, i hope, this could be a bad thing, who knows. I was adopted.")
    # print(rh.orders.get_all_stock_orders()[:10])
    return 'SUCCESS'
robinhoodLogin()

def epoch_to_pst_time(epoch_time):
    epochUTC = dt.utcfromtimestamp(epoch_time)
    epochUTC = epochUTC.replace(tzinfo=pytz.UTC)
    timezonePST = epochUTC.astimezone(pytz.timezone('America/Los_Angeles'))
    epochInFormat = timezonePST.strftime("%H%M")
    return int(epochInFormat)

def checkSpecificTime():
    timezonePST = pytz.timezone('America/Los_Angeles')
    currentUTC = dt.now(pytz.utc)
    currentPST = currentUTC.astimezone(timezonePST)
    currentPSTHM_format = currentPST.strftime("%H%M")
    return int(currentPSTHM_format)

def runScriptWithinTimeframe():

    # current_date = dt.now()
    # # check if current date is a holiday
    # switcher = {
    #     dt.date(2023, 1, 2): "New Year's Day",
    #     dt.date(2023, 1, 16): "Martin Luther King, Jr. Day",
    #     dt.date(2023, 2, 20): "Washington’s Birthday",
    #     dt.date(2023, 4, 7): "Good Friday",
    #     dt.date(2023, 5, 29): "Memorial Day",
    #     dt.date(2023, 6, 19): "Juneteenth National Independence Day",
    #     dt.date(2023, 7, 4): "Independence Day",
    #     dt.date(2023, 9, 4): "Labor Day",
    #     dt.date(2023, 11, 23): "Thanksgiving Day",
    #     dt.date(2023, 12, 25): "Christmas",
    #     dt.date(2023, 7, 3): "Day before Independence Day (1 PM Eastern Close)",
    #     dt.date(2023, 11, 24): "The Day Following Thanksgiving (1 PM Eastern Close)",
    #     dt.date(2023, 12, 24): "Christmas Eve (1 PM Eastern Close)",

    #     dt.date(2024, 1, 1): "New Year's Day",
    #     dt.date(2024, 1, 15): "Martin Luther King, Jr. Day",
    #     dt.date(2024, 2, 19): "Washington’s Birthday",
    #     dt.date(2024, 3, 29): "Good Friday",
    #     dt.date(2024, 5, 27): "Memorial Day",
    #     dt.date(2024, 6, 19): "Juneteenth National Independence Day",
    #     dt.date(2024, 7, 4): "Independence Day",
    #     dt.date(2024, 9, 2): "Labor Day",
    #     dt.date(2024, 11, 28): "Thanksgiving Day",
    #     dt.date(2024, 12, 25): "Christmas",
    #     dt.date(2024, 7, 3): "Day before Independence Day (1 PM Eastern Close)",
    #     dt.date(2024, 11, 29): "The Day Following Thanksgiving (1 PM Eastern Close)",
    #     dt.date(2024, 12, 24): "Christmas Eve (1 PM Eastern Close)"
    # }

    # holiday_name = switcher.get(current_date.date(), None)
    # is_weekend = current_date.weekday() in [5, 6] # 5 is Saturday and 6 is Sunday

    # if holiday_name:
    #     print(f"Today is {holiday_name}, NASDAQ and NYSE are closed.")
    # elif is_weekend:
    #     print("NASDAQ and NYSE are closed on weekends.")
    # else:
    #     print("NASDAQ and NYSE are open today.")

    currentPST = checkSpecificTime()
    print(currentPST)
    if currentPST < startBotAt or currentPST > stopBotAt:
        print(f"sleeping {currentPST}")
        time.sleep(25)
        return runScriptWithinTimeframe()
runScriptWithinTimeframe()

def buyStock(stock, shareQuantity, current_price, deltaP):

    global tradesMade
    if ((tradesMade*budget) >= maxBudget):
        sys.exit(0)

    # data = request.get_json()
    # print(data)
    stockName = stock
    shareQuantity = shareQuantity
    stockPrice = current_price
    stockDeltaP = deltaP
    print(f"current_price FROM BUY DEF {stock} {stockPrice}")
    print(f"deltaP {deltaP}")
    limitBuyPrice = stockPrice * (1 + (stockDeltaP * 0.1 * 0.01))
    limitBuyPrice = round(limitBuyPrice, 4)
    print(f"limitBuyPrice {limitBuyPrice}")
    # limitSellPrice = stockPrice * (1 - (stockDeltaP * 0.1 * 0.01))
    limitSellPrice = stockPrice * (1 - 0.001) #Try selling the stock immediately after you buy it for stockPrice
    limitSellPrice = round(limitSellPrice, 4)

    # Log in to Robinhood
    robinhoodLogin()

    # print(rh.orders.order_buy_fractional_by_price('AAPL', 10, timeInForce='gfd', extendedHours=True))
    # rh.orders.order_buy_limit(stockName, shareQuantity, limitBuyPrice, timeInForce='gfd', extendedHours=True)
    # You have recieved a 2FA code on your phone, or through your authenticator app for Robinhood:

    # robin_stocks.robinhood.orders.get_all_open_stock_orders(info=None)[source]
    # Returns a list of all the orders that are currently open.
    # Parameters:	info (Optional[str]) – Will filter the results to get a specific value.
    # Returns:	Returns a list of dictionaries of key/value pairs for each order. If info parameter is provided, a list of strings is returned where the strings are the value of the key that matches info.

    # robin_stocks.robinhood.orders.cancel_stock_order(orderID)[source]
    # Cancels a specific order.
    # Parameters:	orderID (str) – The ID associated with the order. Can be found using get_all_stock_orders(info=None).
    # Returns:	Returns the order information for the order that was cancelled.

    # robin_stocks.robinhood.orders.get_stock_order_info(orderID)[source]
    # Returns the information for a single order.
    # Parameters:	orderID (str) – The ID associated with the order. Can be found using get_all_orders(info=None) or get_all_orders(info=None).
    # Returns:	Returns a list of dictionaries of key/value pairs for the order.

    # if it is a buy, set the limit price to be 0.75% higher than the current price, say under the order "The order will not execute if the price is higher than *price of current stock (multiplied by 1.75)*"
    # Actually, they have the option to select what percentage they're willing to go above, or below, the current value of the stock, the default for the market order is 0.75%
    #rh.orders.order_buy_limit(stockName, shareQuantity, limitBuyPrice, timeInForce='gfd', extendedHours=True, jsonify=True)
    for obj in stockBlackList:
        if obj['stock'] == stockName:
            # if checkSpecificTime()-obj['buytime'] >= 5:
            if checkSpecificTime()-obj['buytime'] >= 5000:
                obj['buytime'] = checkSpecificTime()
            else:
                return "Failed"
    rh.orders.order_buy_market(stockName, shareQuantity, timeInForce='gfd', extendedHours=True, jsonify=True)
    stockBlackList.append({'stock': stockName, 'buytime': checkSpecificTime()})
    tradesMade += 1
    # For Second Strategy (Trying turning on the bot at 6:30)
    # time.sleep(0.5)
    # rh.orders.order_buy_market(stockName, shareQuantity*2, timeInForce='gfd', extendedHours=True, jsonify=True)

    idOfOrder = ""
    def checkQueueBeforeSellTrigger():
        nonlocal idOfOrder
        openOrders = rh.orders.get_all_open_stock_orders()
        if len(openOrders) < 1:
            print("trade executed")
        else:
            print('arup')
            for i in range(len(openOrders)):
                print(openOrders[i])
                if shareQuantity == round(float(openOrders[i]['quantity'])) and 'buy' == openOrders[i]['side']:
                    idOfOrder = openOrders[i]['id']
                    print("nono")
                    print(idOfOrder)
                    time.sleep(1)
                    return checkQueueBeforeSellTrigger()
            print("trade executed!")
    checkQueueBeforeSellTrigger()
    
    limitSellPrice = limitBuyPrice
    # rh.orders.order_sell_limit(stockName, shareQuantity, limitSellPrice, timeInForce='gfd', extendedHours=True, jsonify=True)
    # if stockDeltaP <= 6:
        # Do an API request and continous check the latest price of this stock and if it is 0.1% above the limit buy, sell it at the limitbuy, if it is 0.2% down trigger a limit sell at 0.3% below limitbuy 
    # rh.orders.order_sell_trailing_stop(stockName, shareQuantity, 1, trailType='percentage', timeInForce='gfd', extendedHours=True, jsonify=True)
    # else:
    time.sleep(2.5)
    print("test1")
    print(idOfOrder)
    recentHistoricalOrders = rh.orders.get_all_stock_orders()[:8]
    averagePriceBoughtForStock = 0
    for obj in recentHistoricalOrders:
        if obj['id'] == idOfOrder:
            print(obj)
            averagePriceBoughtForStock = float(obj['average_price'])
            print(f'Average_Price {averagePriceBoughtForStock}')
            break
    print(averagePriceBoughtForStock)
    # trailingSellPrice = averagePriceBoughtForStock*0.98 #Try selling the stock immediately after you buy it for stockPrice
    # trailingSellPrice2 = averagePriceBoughtForStock*0.99 #Try selling the stock immediately after you buy it for stockPrice
    # trailingSellPrice = averagePriceBoughtForStock*0.02 #Try selling the stock immediately after you buy it for stockPrice
    # trailingSellPrice2 = averagePriceBoughtForStock*0.01 #Try selling the stock immediately after you buy it for stockPrice
    limitSellPrice_9P = averagePriceBoughtForStock*1.09 #Try selling the stock immediately after you buy it for stockPrice
    limitSellPrice_8P = averagePriceBoughtForStock*1.08 #Try selling the stock immediately after you buy it for stockPrice
    trailingSellPricePercent = 2 #Try selling the stock immediately after you buy it for stockPrice
    trailingSellPrice2Percent = 1 #Try selling the stock immediately after you buy it for stockPrice
    print(trailingSellPricePercent)
    AVGPriceBought_str = str(averagePriceBoughtForStock)
    if "." in AVGPriceBought_str:
        roundBy = len(AVGPriceBought_str.split(".")[1])
    else:
        roundBy = 0
    # trailingSellPrice = round(averagePriceBoughtForStock-trailingSellPrice, 2)
    # trailingSellPrice = math.floor((averagePriceBoughtForStock-trailingSellPrice)*100)/100
    # trailingSellPrice2 = math.floor((averagePriceBoughtForStock-trailingSellPrice2)*100)/100
    # trailingSellPrice = round(trailingSellPrice, 4)
    # trailingSellPrice2 = round(trailingSellPrice2, 4)
    # Do the time check at teh very top AFTER robinhood login (so you know if you need to log in) to loop through and sleep until 6:35, and each time the script loops, if the time is > 700, it exits the script, and you start it (and it won't do anything until 635 <= x <= 638 )
    # print(rh.orders.get_all_stock_orders()[:8])
    # print(rh.orders.get_all_stock_orders()['id'][])
    # rh.orders.order_sell_trailing_stop(stockName, shareQuantity, 1, trailType='percentage', timeInForce='gfd', extendedHours=True, jsonify=True)
    # rh.orders.order_trailing_stop(stockName, shareQuantity, 'sell', 2, trailType='percentage', priceType='ask_price', timeInForce='gfd', extendedHours=True, jsonify=True)
    print(trailingSellPricePercent)
    # if (averagePriceBoughtForStock > 1.15):
    shareQuantity_firstHalf = math.floor(shareQuantity/2)
    shareQuantity_secondHalf = shareQuantity-shareQuantity_firstHalf
    time.sleep(2)
    # rh.orders.order_sell_trailing_stop(stockName, shareQuantity_firstHalf, trailingSellPrice, trailType='amount', timeInForce='gfd')
    # # ##AYO#rh.orders.order_sell_trailing_stop(stockName, shareQuantity_firstHalf, trailingSellPricePercent, trailType='percentage', timeInForce='gfd')
    rh.orders.order_sell_limit(stockName, shareQuantity_firstHalf, limitSellPrice_9P, timeInForce='gfd')
    time.sleep(6)
    rh.orders.order_sell_limit(stockName, shareQuantity_secondHalf, limitSellPrice_8P, timeInForce='gfd')
    # rh.orders.order_sell_trailing_stop(stockName, shareQuantity_secondHalf, trailingSellPrice2, trailType='amount', timeInForce='gfd')
    # # ##AYO#rh.orders.order_sell_trailing_stop(stockName, shareQuantity_secondHalf, trailingSellPrice2Percent, trailType='percentage', timeInForce='gfd')
    # time.sleep(6)
    # rh.orders.order_sell_trailing_stop(stockName, shareQuantity_firstHalf, trailingSellPrice, trailType='amount', timeInForce='gfd')
    # time.sleep(1)
    # rh.orders.order_sell_trailing_stop(stockName, shareQuantity_secondHalf+1, trailingSellPrice2, trailType='amount', timeInForce='gfd')
    # rh.orders.order_sell_trailing_stop(stockName, shareQuantity, trailingSellPrice, trailType='amount', timeInForce='gfd')
    print(roundBy)
    print("test2")
    # Submits a limit order to be executed once a certain price is reached.
    # Parameters:
    # symbol (str) – The stock ticker of the stock to purchase.
    # quantity (int) – The number of stocks to buy.
    # limitPrice (float) – The price to trigger the buy order.
    # timeInForce (Optional[str]) – Changes how long the order will be in effect for. ‘gtc’ = good until cancelled. ‘gfd’ = good for the day.
    # extendedHours (Optional[str]) – Premium users only. Allows trading during extended hours. Should be true or false.
    # jsonify (Optional[str]) – If set to False, function will return the request object which contains status code and headers.
    # Returns:
    # Dictionary that contains information regarding the purchase of stocks, such as the order id, the state of order (queued, confired, filled, failed, canceled, etc.), the price, and the quantity.

    # print(rh.orders.order_buy_fractional_by_price('AAPL', 10, timeInForce='gfd', extendedHours=True, jsonify=True))

    return "SUCCCUSSS"

# @app.route('/bubbles_script', methods=['POST'])

def run_script():

    global topStockData
    # print(f"topstockdata ${topStockData}")
    # data = request.get_json()
    # # Equivalent to about 2 years of stock callback, assuming we will only ever do a maximum of 1 year or so, meaning the user tried to change the HTML in the inspector
    # if (int(data['stockDeltaPTimespan']) > 1000000):
    #     return "Oi m8! Trying to overload the server, ey?"
    def run_bubbles(result_list, currentCPUProcess, maxCPUProcesses):
        # Chosen interval (in minutes)
        # chosenInterval = int(data['stockDeltaPTimespan'])
        # chosenInterval = 5
        chosenInterval = 1
        # The top 200 stocks, will be null if the user just started the session because it hasn't sorted through 5,000+ stocks yet
        # topStockData = data['stockData']
        print(chosenInterval)
        global topStockData
        # print(topStockData)

        # Getting list of all NASDAQ-traded stocks
        url = 'https://www.nasdaqtrader.com/dynamic/SymDir/nasdaqtraded.txt'
        response = requests.get(url)
        lines = response.text.split('\n')

        # Extracting the names of all stocks
        nasdaq_stocks = [line.split('|')[1] for line in lines if "Common Stock" in line]

        # API URL for historical data
        #hist_endpoint = f"https://api.polygon.io/v2/aggs/ticker/{symbol}/range/1/minute/{from_date}/{to_date}?apiKey=<your API key>"

        # API URL for current data
        #current_endpoint = 'https://finnhub.io/api/v1/quote'

        # API key
        api_key = 'FF3L2jIzaz621a5yNwdsA7FWhkRZcO3z'

        # How many API requests can be made
        #api_rateLimit = 30

        # Update bubble stock on interval (in seconds) for smooth bubble animation
        #bubblesUpdateInterval = 1/0.25

        # Amount a bubbles stock will need to update before the script comes back to the stock
        #bubblesUpdateAmount = math.ceil((rankingAmount/api_rateLimit)*bubblesUpdateInterval)

        # To bubbles format
        bubbles_JSON = []
        bubbles_JSON2 = []
        start_price = 0

        # Get the stock quotes and update them continously
        def bubblesUpdate(stock, stockVolume, AVGDailyVolume, yesterdayClosingPrice):
            global topStockData
            # Stock symbol
            symbol = stock

            # Interval (in seconds)
            choseIntervalFormated = chosenInterval*60
            #print("hello")

            # Test interval to minus start and end time by for testing to go back to when trading hours were (in seconds)
            test_interval = 0
            # Set the current time
            current_time = int(time.time()) - test_interval
            #print(current_time)

            # start_time = (int(dt.datetime.timestamp(dt.datetime.now() - dt.timedelta(minutes=chosenInterval))) - test_interval)*1000
            # start_time = 1678212049000-(countOfStockTries*10*1000)
            #print(start_time)
            # end_time = (int(dt.datetime.timestamp(dt.datetime.now())) - test_interval)*1000
            # end_time = 1678212349000-(countOfStockTries*10*1000)
            #print(end_time)

            # Timestamp to start getting stock quotes, current time minus the interval(minutes ago) the user selected
            start_timestamp_data = dt.fromtimestamp(current_time-choseIntervalFormated)
            start_formatted_date = start_timestamp_data.strftime('%Y-%m-%d')
            start_timestamp_dataVolumeEndpoint = dt.fromtimestamp(current_time-4320000)
            start_formatted_dateVolumeEndpoint = start_timestamp_dataVolumeEndpoint.strftime('%Y-%m-%d')
            # Timestamp of current stock quote in the current time, to calculate the difference in P/V from the first timestamp data point, to the newest data point
            end_timestamp_data = dt.fromtimestamp(current_time)
            end_formatted_date = end_timestamp_data.strftime('%Y-%m-%d')
            # start_formatted_date = 1678463700*1000
            # end_formatted_date = (current_time-154776)*1000
            # API URL for historical data
            hist_endpoint = f"https://api.polygon.io/v2/aggs/ticker/{symbol}/range/1/minute/{start_formatted_date}/{end_formatted_date}?apiKey={api_key}"
            # if (start_formatted_date != end_formatted_date):
            #     Today_hist_endpoint = f"https://api.polygon.io/v2/aggs/ticker/{symbol}/range/1/minute/{end_formatted_date}/{end_formatted_date}?apiKey={api_key}"
            # Last_30Days_hist_endpoint = f"https://api.polygon.io/v2/aggs/ticker/{symbol}/range/1/day/{start_formatted_date}/{end_formatted_date}?apiKey={api_key}"
            if (stockVolume == 0):
                # 50 days ago to now
                totalVolume_endpoint = f"https://api.polygon.io/v2/aggs/ticker/{symbol}/range/1/day/{start_formatted_dateVolumeEndpoint}/{end_formatted_date}?apiKey={api_key}"


            # Check if any errors occur while retrieving a quote, if not, put the response in "hist_data"
            def hist_data_JSONError(url_endpoint):
                # Send the historical data API request and get the response
                #hist_response = requests.get(hist_endpoint, params=hist_params)
                endpoint_response = requests.get(url_endpoint)
                #hist_response.raise_for_status()
                try:
                    # Parse the response as JSON
                    endpoint_data = endpoint_response.json()
                    return endpoint_data
                except json.JSONDecodeError as e:
                    # Handle any JSON syntax errors
                    print(f"JSON syntax error: {e}")
                    return False
            hist_data = hist_data_JSONError(hist_endpoint)
            if hist_data == False:
                return False

            if (stockVolume == 0):
                hist_data_totalVolume = hist_data_JSONError(totalVolume_endpoint)
                if hist_data_totalVolume == False:
                    return False
                
                try:
                    value = hist_data_totalVolume["results"]
                except KeyError:
                    print(f"No Data {stock}")
                    return False

                FiftyDayVolume = 0
                yesterdayClosingPrice = hist_data_totalVolume["results"][-2]["c"]
                for FiftyDayVolumei in range(len(hist_data_totalVolume["results"])):
                    FiftyDayVolume += hist_data_totalVolume["results"][FiftyDayVolumei]["v"]

                AVGDailyVolume = FiftyDayVolume/50

            # def noDataOrError(hist_data):

            #     if hist_data == False:
            #         return False
            #     print('AYO?')
            #     print(json.dumps(hist_data))
            #     print('AYUP')
            #     if 'no_data' in json.dumps(hist_data):
            #         print('here3')
            #         # Use in real run
            #         return False
            #         # Use in practice
            #         #return False
            #     if 'error' in json.dumps(hist_data):
            #         print('here4')
            #         time.sleep(20)
            #         hist_data = hist_data_JSONError()
            #         return noDataOrError(hist_data)
            #     print('AYO?6')
            #     print(json.dumps(hist_data))
            #     print('AYUP6')
            #     return hist_data
            # print('AYUP3')
            # #noDataOrError(hist_data,hist_response)
            # hist_data = noDataOrError(hist_data)
            # if hist_data == False:
            #     return False

            #print(json.dumps(hist_data))
            #print('AYUP4')


            # Get the price and volume from 5 minutes ago
            #random_number = random.randint(1, 8)

            # Get a specific index of the returned response, but if this index is not there, it means the stock has no data (meaning it's not a stock that's traded, and thus NasDaq doesn't update it)
            try:
                value = hist_data["results"]
                # countOfStockTries = 0
            except KeyError:
                print(f"No Data {stock}")
                # countOfStockTries += 1
                # if (countOfStockTries > 50):
                #     return False
                # return bubblesUpdate(stock, countOfStockTries)
                return False
            # if (stockVolume == 0):
            #     try:
            #         value = hist_data_totalVolume["results"]
            #     except KeyError:
            #         print(f"No Data {stock}")
            #         return False
            #print(hist_data["results"])
            stockTimeIndex = 2
            # Return False if there is no data from the start time to current time within the desired interval OR if the total volume is below 100,000
            # if len(hist_data["results"]) < stockTimeIndex or hist_data["results"][-1]["v"] < 1000:
            if len(hist_data["results"]) < stockTimeIndex:
                return False
            # Since the quote can return a varying number of candles depending on the stock (it can return three candles in a five minute-interval, or six, or just two, etc..). This code gets the last stock quote and keeps searching the index from end-to-start until it finds another quote with a timestamp equal to, or greater than, the interval the user selected
            while ((hist_data["results"][-1]["t"])-(hist_data["results"][-stockTimeIndex]["t"])) < (choseIntervalFormated*1000):
                stockTimeIndex += 1
                if len(hist_data["results"]) < stockTimeIndex:
                    return False
                if (stockTimeIndex > 100000):
                    print("M8, Too many candles *sad_pepe_face*")
                    return False
            
            stockTimeIndex30s = 2
            while ((hist_data["results"][-1]["t"])-(hist_data["results"][-stockTimeIndex30s]["t"])) < (0.5*60*1000):
                stockTimeIndex30s += 1
                if len(hist_data["results"]) < stockTimeIndex30s:
                    return False
            price_30s_ago = hist_data["results"][-stockTimeIndex30s]["c"] #hist_data['c'][0] #10-random_number
            # stockTimeIndex1min = 2
            # while ((hist_data["results"][-1]["t"])-(hist_data["results"][-stockTimeIndex1min]["t"])) < (1*60*1000):
            #     stockTimeIndex1min += 1
            # price_1min_ago = hist_data["results"][-stockTimeIndex1min]["c"] #hist_data['c'][0] #10-random_number
            stockTimeIndex2min = 2
            while ((hist_data["results"][-1]["t"])-(hist_data["results"][-stockTimeIndex2min]["t"])) < (2*60*1000):
                stockTimeIndex2min += 1
                if len(hist_data["results"]) < stockTimeIndex2min:
                    return False
            price_2min_ago = hist_data["results"][-stockTimeIndex2min]["c"] #hist_data['c'][0] #10-random_number

            stockTimeIndex630 = 2
            while (epoch_to_pst_time((hist_data["results"][-stockTimeIndex630]["t"])/1000) > 630):
                epochOfStock = epoch_to_pst_time((hist_data["results"][-stockTimeIndex630]["t"])/1000)
                # print(f"Epoch conversion: {epochOfStock}")
                stockTimeIndex630 += 1
                if len(hist_data["results"]) < stockTimeIndex630:
                    # pb = Pushbullet("o.cJrqEoYYM3JgU7Wfgl5vccNCi7cwuyky")
                    # pb.push_note(stock, "returned false at stockTimeIndex630")
                    print(f"Reutned False, Epoch conversion: {epochOfStock}")
                    return False
            price_630_ago = hist_data["results"][-stockTimeIndex630]["c"] #hist_data['c'][0] #10-random_number

            # stockTimeInden3min = 2
            # while ((hist_data["results"][-1]["t"])-(hist_data["results"][-stockTimeInden3min]["t"])) < (3*60*1000):
            #     stockTimeInden3min += 1
            # price_3min_ago = hist_data["results"][-stockTimeInden3min]["c"] #hist_data['c'][0] #10-random_number
            # stockTimeIndex4min = 2
            # while ((hist_data["results"][-1]["t"])-(hist_data["results"][-stockTimeIndex4min]["t"])) < (4*60*1000):
            #     stockTimeIndex4min += 1
            # price_4min_ago = hist_data["results"][-stockTimeIndex4min]["c"] #hist_data['c'][0] #10-random_number

            current_price = hist_data["results"][-1]["c"] #hist_data['c'][-1] #30-random_number
            # if ((current_price/price_30s_ago) < 1) or ((current_price/price_1min_ago) < 1) or ((current_price/price_2min_ago) < 1) or ((current_price/price_3min_ago) < 1) or ((current_price/price_4min_ago) < 1):
            deltaP30s = ((current_price/price_30s_ago)*100)-100
            deltaP2min = ((current_price/price_2min_ago)*100)-100
            # deltaP1min = ((current_price/price_1min_ago)*100)-100
            # if current_price > price_30s_ago:
            if current_price >= price_30s_ago:
                changeInDeltaP = "positive"
            else:
                changeInDeltaP = "negative"
            
            if AVGDailyVolume < 110000:
                return False

            # if current_price < 1.10:
            #     return False

            price_interval_ago = hist_data["results"][-stockTimeIndex]["c"] #hist_data['c'][0] #10-random_number
            #print(f'{stock} interval ago {price_interval_ago} and {hist_data["results"][-stockTimeIndex]["t"]}')
            # volume_interval_ago = hist_data["results"][-stockTimeIndex]["v"] #hist_data['v'][0] #10-random_number
            #print(f'{stock} NOW ago {current_price} and {hist_data["results"][-1]["t"]}')
            # current_volume = hist_data["results"][-1]["v"] #hist_data['v'][-1] #50-random_number
            if (stockVolume == 0):
                current_volume = hist_data_totalVolume["results"][-1]["v"] #hist_data['v'][-1] #50-random_number
                # Try replacing this with current_volume = AVGDailyVolume
            else:
                current_volume = stockVolume
            current_volumeWeighted = hist_data["results"][-1]["vw"] #hist_data['v'][-1] #50-random_number
            deltaT = hist_data["results"][-1]["t"] #hist_data['v'][-1] #50-random_number
            deltaP = ((current_price/price_interval_ago)*100)-100
            # deltaV = ((current_volume/volume_interval_ago)*100)-100
            tradeVolume = 0
            for tradesi in range(len(hist_data["results"])):
                tradeVolume += hist_data["results"][tradesi]["v"]

            deltaV = (tradeVolume/AVGDailyVolume)*100

            print(f"AVGVolume: {stock} deltaP: {deltaP} DeltaV: {deltaV}")
            # It went up x% in the chosenInterval selected
            # if topStockData != "null" and changeInDeltaP == "positive":
            # if topStockData != "null"  and changeInDeltaP == "positive" and deltaP2min < 18 and deltaV > 900:
            if topStockData != "null" and changeInDeltaP == "positive" and deltaP2min < 18:
                if deltaP30s >= 6:

                    pb = Pushbullet("o.cJrqEoYYM3JgU7Wfgl5vccNCi7cwuyky")
                    pb.push_note(stock, f"Passed vibe check 2, MOSTLY?")

                    if (deltaP30s+6 > deltaP2min):
                        pb = Pushbullet("o.cJrqEoYYM3JgU7Wfgl5vccNCi7cwuyky")
                        pb.push_note(stock, f"Passed vibe check 2, MOSTLY? 2")

                    if (deltaP30s > deltaP2min):
                        pb = Pushbullet("o.cJrqEoYYM3JgU7Wfgl5vccNCi7cwuyky")
                        pb.push_note(stock, f"Passed vibe check 2, MOSTLY? 3")

                    # if (deltaP30s+6 > deltaP2min) and (deltaP30s > deltaP2min):
                    if (deltaP30s+6 > deltaP2min):

                        pb = Pushbullet("o.cJrqEoYYM3JgU7Wfgl5vccNCi7cwuyky")
                        pb.push_note(stock, f"here2 for somereason?")
                        pOrNSince630 = "positive since 630"
                        if (current_price > price_630_ago):
                            pb = Pushbullet("o.cJrqEoYYM3JgU7Wfgl5vccNCi7cwuyky")
                            pb.push_note(stock, f"{stock} 2 Price: {current_price} Volume-Weighted: {current_volumeWeighted} Went Up: {deltaP} AVG Volume is: {AVGDailyVolume} DeltaV is: {deltaV} ALL DeltaP Number: {deltaP30s} {deltaP} {deltaP2min} Since 630: {pOrNSince630}")

                if deltaP30s >= 7 or deltaP >= 7 or deltaP2min >= 7: # Testing at 2, change to 4
                    if (deltaP30s >= 6 and deltaP2min > 8.72) or deltaP30s > 7:

                        pb = Pushbullet("o.cJrqEoYYM3JgU7Wfgl5vccNCi7cwuyky")
                        pb.push_note(stock, f"Passed vibe check, MOSTLY? {yesterdayClosingPrice}")

                        if (deltaP30s+6 > deltaP2min):
                            pb = Pushbullet("o.cJrqEoYYM3JgU7Wfgl5vccNCi7cwuyky")
                            pb.push_note(stock, f"Passed vibe check, MOSTLY? 2")

                        if (deltaP30s > deltaP2min):
                            pb = Pushbullet("o.cJrqEoYYM3JgU7Wfgl5vccNCi7cwuyky")
                            pb.push_note(stock, f"Passed vibe check, MOSTLY? 3")

                        # if (deltaP30s+6 > deltaP2min) and (deltaP30s > deltaP2min):
                        if (deltaP30s+6 > deltaP2min and yesterdayClosingPrice < current_price):

                            pb = Pushbullet("o.cJrqEoYYM3JgU7Wfgl5vccNCi7cwuyky")
                            pb.push_note(stock, f"here1 for somereason?")
                            pOrNSince630 = "positive since 630"
                            # if (price_630_ago > current_price):
                            if (price_630_ago*1.03 > current_price):
                                pOrNSince630 = "negative since 630"
                                pb = Pushbullet("o.cJrqEoYYM3JgU7Wfgl5vccNCi7cwuyky")
                                pb.push_note(stock, f"{stock} Price: {current_price} Volume-Weighted: {current_volumeWeighted} Went Up: {deltaP} AVG Volume is: {AVGDailyVolume} DeltaV is: {deltaV} ALL DeltaP Number: {deltaP30s} {deltaP} {deltaP2min} Since 630: {pOrNSince630}")
                            else:
                                pb = Pushbullet("o.cJrqEoYYM3JgU7Wfgl5vccNCi7cwuyky")
                                pb.push_note(stock, f"{stock} Price: {current_price} Volume-Weighted: {current_volumeWeighted} Went Up: {deltaP} AVG Volume is: {AVGDailyVolume} DeltaV is: {deltaV} ALL DeltaP Number: {deltaP30s} {deltaP} {deltaP2min} Since 630: {pOrNSince630}")
                                global budget
                                shareQuantity = math.floor(budget/current_price) 
                                print(f"current_price FROM NOT BUY {stock} {current_price}")
                                print(f"current_price C value FROM NOT BUY {stock} {hist_data['results'][-1]}")
                                print(f"AVGVolume {stock} {AVGDailyVolume} DeltaV {deltaV}")
                                buyStock(stock, shareQuantity, current_price, deltaP)



            # Print the results
            # print(f'Price {chosenInterval} minutes ago: {price_interval_ago:.2f}')
            # print(f'Volume {chosenInterval} minutes ago: {volume_interval_ago:,}')
            # print(f'Current price: {current_price:.2f}')
            # print(f'Current volume: {current_volume:,}')
            # print(f'{chosenInterval} minute P change: {deltaP}')
            # print(f'{chosenInterval} minute V change: {deltaV}')

            # To bubbles format
            stock_JSON = {
                'stock': symbol,
                'price': current_price,
                'volume': current_volume,
                'delta_p': deltaP,
                'delta_t': deltaT,
                'delta_v': deltaV,
                'vwap': current_volumeWeighted,
                'start_price': start_price,
                'AVGDailyVolume': AVGDailyVolume,
                'yesterdayClosing': yesterdayClosingPrice
            }
            print(f"{stock} TradedVolume: {tradeVolume} AVGDailyVolume: {AVGDailyVolume} AVG. volume: {deltaV} yesterdayClosingPrice: {yesterdayClosingPrice}")

            bubbles_JSON.append(stock_JSON)

            #time.sleep(0.1)
            return stock_JSON

        # Compile all the stocks itgot from each iteration of the bubblesUpdate loop into a singular JSON
        def JSONToBeSentToApp(stocksSorted):
            bubbles_JSON = []
            bubbles_JSON4 = []
            bubbles_JSON2 = []
            for i in range(len(stocksSorted)):
                bubblesUpdate_result = bubblesUpdate(stocksSorted[i]['stock'], stocksSorted[i]['volume'], stocksSorted[i]['AVGDailyVolume'], stocksSorted[i]['yesterdayClosing'])
                if bubblesUpdate_result != False:
                    bubbles_JSON2.append(bubblesUpdate_result)
                else:
                    print("was FAlsE")
                    bubbles_JSON2.append(stocksSorted[i])

            return bubbles_JSON2

        # if the top 200 stocks have already been selected, only search and update those in the bubblesUpdate function, else, scan through all 5,000+ stocks and find the top 200 absolute value performers
        if topStockData != "null":
            # print(f"List Index OUT OF RANGE? {json.dumps(topStockData)}")
            # print(f"List Index OUT OF RANGE 2? {(json.dumps(topStockData)).split('[')}")
            topStockData2 = "[" + (((json.dumps(topStockData)).split("["))[2])[:-3] + "]"
            topStockData2 = json.loads(topStockData2)
            np_topStockData = np.array(topStockData2)
            split_topStockData = np.array_split(np_topStockData, maxCPUProcesses)
            # Loop through each of the top 200 stocks, but only for a certain segment of the list (determined by which CPU thread we're on, e.g. if we're on the 4th CPU thread out of 8, it would choose a array of stocks somewhere in the middle of the stock list on the top 200 list)
            bubbles_JSON4 = JSONToBeSentToApp(split_topStockData[currentCPUProcess-1])
        else:
            # Names of all stocks
            np_nasdaq_stocks = np.array(nasdaq_stocks)
            split_nasdaq_stocks = np.array_split(np_nasdaq_stocks, maxCPUProcesses)
            stockCount = 0
            # Loop through each stocks to get its quotes, but only for a certain segment of the total number of stocks on the NasDaq (determined by which CPU thread we're on, e.g. if we're on the 4th CPU thread out of 8, it would choose a array of stocks somewhere in the middle of the stock list on the NasDaq)
            for stock in split_nasdaq_stocks[currentCPUProcess-1]:
                # Do no if statement limit for real run, this is just the make is faster for testing
                # if stockCount < 30:
                print(stock)
                print(stockCount)
                bubblesUpdate(stock, 0, 0, 0)
                stockCount += 1
            #bubblesUpdate('APPL')

            # Sort the stocks by absolute P value
            # sorted_data = sorted(bubbles_JSON, key=lambda x: abs(x["delta_p"]), reverse=True)
            sorted_data = sorted(bubbles_JSON, key=lambda x: x["volume"], reverse=True)
            # Only take the top 200
            sorted_data = sorted_data[:(math.floor(200*(1/maxCPUProcesses)))]

            bubbles_JSON4 = JSONToBeSentToApp(sorted_data)

        result_list.append(json.dumps(bubbles_JSON4))
        #return json.dumps(bubbles_JSON4)

    # Create a pool of worker processes
    # num_processes = multiprocessing.cpu_count()
    # pool = multiprocessing.Pool(num_processes)

    # # Define input arguments for each script instance
    # script_args_list = []
    # print(num_processes)
    # for i in range(num_processes):
    #     script_args_list.append((i/num_processes)*num_processes)

    # print("args List")
    # print(script_args_list)
    # # Start each script instance as a separate process
    # results = [pool.apply_async(run_bubbles, args=(script_args,num_processes)) for script_args in script_args_list]

    # START, up until "END," the entire section pretty much determines the number of CPU threads available, and runs the part of the script we want on each of those threads, and then compiles all of them together once they've finished, and then sorts them in alphabetical order(so when app.py is run again everytime the Bubbles stocks updates, it will always get back the same list order, which is alphabetical order, so it knows which circle is which stock in the index. It's not sorted by P value because we already have the top 200 stocks based on absolute P value, so sorting by P value wouldn't change anything, and would make it worse as the stocks' positions in the index would change whenever their P values increase or decrease).
    num_instances = multiprocessing.cpu_count()
    if (num_instances > 8):	
        num_instances = 8
    result_list = []
    threads = []
    for i in range(num_instances):
        # print("i start")
        # print(i)
        t = Thread(target=run_bubbles, args=(result_list,i+1,num_instances))
        threads.append(t)
        t.start()

    # Wait for all threads to finish
    for t in threads:
        t.join()

    # Combine the results and return the response
    result_list = [s[1:-1] for s in result_list]
    responseJoin = ','.join(result_list)
    responseSplit = responseJoin.split('},')
    responseSplit = [s + "}" for s in responseSplit[:-1]] + [responseSplit[-1]]
    for i in range(len(responseSplit)):
        responseSplit[i] = json.loads(responseSplit[i])
    response_Sorted = sorted(responseSplit, key=lambda x: x["stock"])
    #responseCompiled = "[{'" + str(int(time.time())) + "': " + str(response_Sorted) + "}]"
    responseCompiled = {int(time.time()): response_Sorted}
    responseCompiledJSON = [responseCompiled]
    print('Wuddap')
    #response2 = response2.replace('\\', '')
    # print(json.dumps(responseCompiledJSON))
    # REPLACE (rewrite) the current data in the bubbles JSON with the new update of the stocks
    # with open('./data/bubbles.json', 'w') as file:
    #     # Write a string to the file
    #     file.write(json.dumps(responseCompiledJSON))


    # if (data['stockData'] == "null"):
    #     with open('./data/bubblesReplaySession.json', 'w') as file:
    #         file.write('[]')

    # with open('./data/bubblesReplaySession.json', 'r') as file:
    #     bubblesReplaySession = file.read()

    # print("Replay session")
    # bubblesReplaySession = json.loads(bubblesReplaySession)
    # # print(json.dumps(bubblesReplaySession))
    # bubblesReplaySession.append(responseCompiled)
    # # ADD the data to the replay session JSON
    # with open('./data/bubblesReplaySession.json', 'w') as file:
    #     file.write(json.dumps(bubblesReplaySession))

    topStockData = responseCompiledJSON
    # print(topStockData)

    if (checkSpecificTime() < startBuyingAt):
        timeBetweenStartTimeAndCurrent = startBuyingAt-checkSpecificTime()
        print(f'timeBetweenStartTimeAndCurrent {timeBetweenStartTimeAndCurrent}')
        time.sleep(timeBetweenStartTimeAndCurrent*60)

    print(checkSpecificTime())
    if (checkSpecificTime() > stopBotAt):
        os.execv(sys.executable, ['python'] + sys.argv)
                
    time.sleep(1)
    return run_script()
    # END

    #print(json.dumps(bubbles_JSON))

    #return json.dumps(bubbles_JSON)
    # file = open("./data/visualize_out_20210429_181546.json", "r")
    # contents = file.read()
    # file.close()
    #return json.dumps(bubbles_JSON)
run_script()

# If the user clicks the button to replay the session, if will just send back the JSON(JSON2) file location of all the Bubbles stock updates that the script has been adding to everytime it updated the stocks
@app.route('/replaybubblessession', methods=['POST'])
def replayingBubblesSession():
    with open('./data/bubblesReplaySession.json', 'r') as file:
        bubblesReplaySession = file.read()
    return bubblesReplaySession


# @app.route('/robinhoodlogin', methods=['POST'])
# def robinhoodLogin():

#     # data = request.get_json()
#     # exchangeAuthentication = data['exchangeAuthentication']
#     # Log in to Robinhood
#     rh.login(username='donaldcottman@gmail.com', password='Bubbles2024$', store_session=True)
    
#     return 'SUCCESS'

# @app.route('/buystock', methods=['POST'])
# def buyStock(stock, shareQuantity, current_price, deltaP):

#     # data = request.get_json()
#     # print(data)
#     stockName = stock
#     shareQuantity = shareQuantity
#     stockPrice = current_price
#     stockDeltaP = deltaP
#     limitBuyPrice = stockPrice * (1 + (stockDeltaP * 0.1 * 0.01))
#     limitBuyPrice = round(limitBuyPrice, 2)
#     # limitSellPrice = stockPrice * (1 - (stockDeltaP * 0.1 * 0.01))
#     limitSellPrice = stockPrice * (1 - 0.001)
#     limitSellPrice = round(limitSellPrice, 2)

#     # Log in to Robinhood
#     robinhoodLogin()

#     # print(rh.orders.order_buy_fractional_by_price('AAPL', 10, timeInForce='gfd', extendedHours=True))
#     # rh.orders.order_buy_limit(stockName, shareQuantity, limitBuyPrice, timeInForce='gfd', extendedHours=True)
#     # You have recieved a 2FA code on your phone, or through your authenticator app for Robinhood:

#     # robin_stocks.robinhood.orders.get_all_open_stock_orders(info=None)[source]
#     # Returns a list of all the orders that are currently open.
#     # Parameters:	info (Optional[str]) – Will filter the results to get a specific value.
#     # Returns:	Returns a list of dictionaries of key/value pairs for each order. If info parameter is provided, a list of strings is returned where the strings are the value of the key that matches info.

#     # robin_stocks.robinhood.orders.cancel_stock_order(orderID)[source]
#     # Cancels a specific order.
#     # Parameters:	orderID (str) – The ID associated with the order. Can be found using get_all_stock_orders(info=None).
#     # Returns:	Returns the order information for the order that was cancelled.

#     # robin_stocks.robinhood.orders.get_stock_order_info(orderID)[source]
#     # Returns the information for a single order.
#     # Parameters:	orderID (str) – The ID associated with the order. Can be found using get_all_orders(info=None) or get_all_orders(info=None).
#     # Returns:	Returns a list of dictionaries of key/value pairs for the order.

#     # if it is a buy, set the limit price to be 0.75% higher than the current price, say under the order "The order will not execute if the price is higher than *price of current stock (multiplied by 1.75)*"
#     # Actually, they have the option to select what percentage they're willing to go above, or below, the current value of the stock, the default for the market order is 0.75%
#     rh.orders.order_buy_limit(stockName, shareQuantity, limitBuyPrice, timeInForce='gfd', extendedHours=True, jsonify=True)

#     idOfOrder = ""
#     def checkQueueBeforeSellTrigger():
#         openOrders = rh.orders.get_all_open_stock_orders()
#         if len(openOrders) < 1:
#             print("trade executed")
#         else:
#             print('arup')
#             for i in openOrders:
#                 if limitBuyPrice == openOrders[i]['price']:
#                     idOfOrder = openOrders[i]['id']
#                     print("nono")
#                     time.sleep(1)
#                     return checkQueueBeforeSellTrigger()
#             print("trade executed!")
#     checkQueueBeforeSellTrigger()
    
#     limitSellPrice = limitBuyPrice
#     rh.orders.order_sell_limit(stockName, shareQuantity, limitSellPrice, timeInForce='gfd', extendedHours=True, jsonify=True)
#     # Submits a limit order to be executed once a certain price is reached.
#     # Parameters:
#     # symbol (str) – The stock ticker of the stock to purchase.
#     # quantity (int) – The number of stocks to buy.
#     # limitPrice (float) – The price to trigger the buy order.
#     # timeInForce (Optional[str]) – Changes how long the order will be in effect for. ‘gtc’ = good until cancelled. ‘gfd’ = good for the day.
#     # extendedHours (Optional[str]) – Premium users only. Allows trading during extended hours. Should be true or false.
#     # jsonify (Optional[str]) – If set to False, function will return the request object which contains status code and headers.
#     # Returns:
#     # Dictionary that contains information regarding the purchase of stocks, such as the order id, the state of order (queued, confired, filled, failed, canceled, etc.), the price, and the quantity.

#     # print(rh.orders.order_buy_fractional_by_price('AAPL', 10, timeInForce='gfd', extendedHours=True, jsonify=True))

#     return "SUCCCUSSS"

@app.route('/sellstock', methods=['POST'])
def sellStock(stock, shareQuantity, current_price, deltaP):

    # data = request.get_json()
    # print(data)
    stockName = stock
    shareQuantity = shareQuantity
    stockPrice = current_price
    stockDeltaP = deltaP
    limitBuyPrice = stockPrice * (1 + (stockDeltaP * 0.1 * 0.01))
    limitBuyPrice = round(limitBuyPrice, 2)
    # limitSellPrice = stockPrice * (1 - (stockDeltaP * 0.1 * 0.01))
    limitSellPrice = stockPrice * (1 - 0.001)
    limitSellPrice = round(limitSellPrice, 2)

    # Log in to Robinhood
    robinhoodLogin()
    rh.orders.order_sell_limit(stockName, shareQuantity, limitSellPrice, timeInForce='gfd', extendedHours=True, jsonify=True)

    return 'Successfully SOLD! Probably, it\'s actually just queued, but maybe it will sell. Good Luck!'


@app.route('/showrobinhoodhistory', methods=['POST'])
def showRobinhoodHistory(limitBuyPrice):

    # Log in to Robinhood
    robinhoodLogin()
    openOrders = rh.orders.get_all_open_stock_orders()

    print('arup')
    if limitBuyPrice == openOrders[0]['price']:
        print("nono")
        return showRobinhoodHistory(limitBuyPrice)
    else:
        print("trade executed")

    # print(openOrders[0]['price'])
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



