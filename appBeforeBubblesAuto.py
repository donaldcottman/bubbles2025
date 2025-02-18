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


@app.route('/bubbles_script', methods=['POST'])
def run_script():

    startAt = 635 # Time of day to start, in 00:00 to 23:59 form without the colon
    def checkSpecificTime():
        timezonePST = pytz.timezone('America/Los_Angeles')
        currentUTC = dt.now(pytz.utc)
        currentPST = currentUTC.astimezone(timezonePST)
        currentPSTHM_format = currentPST.strftime("%H%M")
        return int(currentPSTHM_format)

    def runScriptWithinTimeframe():
        currentPST = checkSpecificTime()
        print(currentPST)
        if currentPST < startAt:
            print(f"sleeping {currentPST}")
            time.sleep(25)
            return runScriptWithinTimeframe()
    runScriptWithinTimeframe()

    data = request.get_json()
    # Equivalent to about 2 years of stock callback, assuming we will only ever do a maximum of 1 year or so, meaning the user tried to change the HTML in the inspector
    if (int(data['stockDeltaPTimespan']) > 1000000):
        return "Oi m8! Trying to overload the server, ey?"
    def run_bubbles(result_list, currentCPUProcess, maxCPUProcesses):
        # Chosen interval (in minutes)
        chosenInterval = int(data['stockDeltaPTimespan'])
        # The top 200 stocks, will be null if the user just started the session because it hasn't sorted through 5,000+ stocks yet
        topStockData = data['stockData']
        print(chosenInterval)
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

        # Amount of stocks to be rendered
        # rankingAmount = 200
        # topStockPicks = False

        # Update bubble stock on interval (in seconds) for smooth bubble animation
        #bubblesUpdateInterval = 1/0.25

        # Amount a bubbles stock will need to update before the script comes back to the stock
        #bubblesUpdateAmount = math.ceil((rankingAmount/api_rateLimit)*bubblesUpdateInterval)

        # To bubbles format
        bubbles_JSON = []
        bubbles_JSON2 = []
        # bubbles_JSONString = ""
        start_price = 0

        # Get the stock quotes and update them continously
        def bubblesUpdate(stock, stockVolume, AVGDailyVolume):
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

            # start_time = (int(dt.timestamp(dt.now() - dt.timedelta(minutes=chosenInterval))) - test_interval)*1000
            # start_time = 1678212049000-(countOfStockTries*10*1000)
            #print(start_time)
            # end_time = (int(dt.timestamp(dt.now())) - test_interval)*1000
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
            price_interval_ago = hist_data["results"][-stockTimeIndex]["c"] #hist_data['c'][0] #10-random_number
            #print(f'{stock} interval ago {price_interval_ago} and {hist_data["results"][-stockTimeIndex]["t"]}')
            # volume_interval_ago = hist_data["results"][-stockTimeIndex]["v"] #hist_data['v'][0] #10-random_number
            current_price = hist_data["results"][-1]["c"] #hist_data['c'][-1] #30-random_number
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
            }
            print(f"{stock} TradedVolume: {tradeVolume} AVGDailyVolume: {AVGDailyVolume} AVG. volume {deltaV}")

            bubbles_JSON.append(stock_JSON)

            #time.sleep(0.1)
            return stock_JSON

        # Compile all the stocks itgot from each iteration of the bubblesUpdate loop into a singular JSON
        def JSONToBeSentToApp(stocksSorted):
            bubbles_JSON = []
            bubbles_JSON4 = []
            bubbles_JSON2 = []
            for i in range(len(stocksSorted)):
                bubblesUpdate_result = bubblesUpdate(stocksSorted[i]['stock'], stocksSorted[i]['volume'], stocksSorted[i]['AVGDailyVolume'])
                if bubblesUpdate_result != False:
                    bubbles_JSON2.append(bubblesUpdate_result)
                else:
                    print("was FAlsE")
                    bubbles_JSON2.append(stocksSorted[i])

            return bubbles_JSON2

        # if the top 200 stocks have already been selected, only search and update those in the bubblesUpdate function, else, scan through all 5,000+ stocks and find the top 200 absolute value performers
        if topStockData != "null":
            topStockData = "[" + (((json.dumps(topStockData)).split("["))[2])[:-3] + "]"
            topStockData = json.loads(topStockData)
            np_topStockData = np.array(topStockData)
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
                # if stockCount < 100:
                print(stock)
                print(stockCount)
                bubblesUpdate(stock, 0, 0)
                stockCount += 1
            #bubblesUpdate('APPL')

            # Sort the stocks by absolute P value
            # sorted_data = sorted(bubbles_JSON, key=lambda x: abs(x["delta_p"]), reverse=True)
            sorted_data = sorted(bubbles_JSON, key=lambda x: x["volume"], reverse=True)
            # Only take the top 200
            sorted_data = sorted_data[:(math.floor(200*(1/maxCPUProcesses)))]
            # topStockPicks = True

            bubbles_JSON4 = JSONToBeSentToApp(sorted_data)

        result_list.append(json.dumps(bubbles_JSON4))
        #return json.dumps(bubbles_JSON4)

    # num_processes = multiprocessing.cpu_count()
    # pool = multiprocessing.Pool(num_processes)

    # script_args_list = []
    # print(num_processes)
    # for i in range(num_processes):
    #     script_args_list.append((i/num_processes)*num_processes)

    # print("args List")
    # print(script_args_list)
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
    # REPLACE (rewrite) the current data in the bubbles JSON with the new update of the stocks
    with open('./data/bubbles.json', 'w') as file:
        # Write a string to the file
        file.write(json.dumps(responseCompiledJSON))


    if (data['stockData'] == "null"):
        with open('./data/bubblesReplaySession.json', 'w') as file:
            file.write('[]')

    with open('./data/bubblesReplaySession.json', 'r') as file:
        bubblesReplaySession = file.read()

    print("Replay session")
    bubblesReplaySession = json.loads(bubblesReplaySession)
    bubblesReplaySession.append(responseCompiled)
    # ADD the data to the replay session JSON
    with open('./data/bubblesReplaySession.json', 'w') as file:
        file.write(json.dumps(bubblesReplaySession))

    return json.dumps(responseCompiledJSON)
    # END

# If the user clicks the button to replay the session, if will just send back the JSON(JSON2) file location of all the Bubbles stock updates that the script has been adding to everytime it updated the stocks
@app.route('/replaybubblessession', methods=['POST'])
def replayingBubblesSession():
    with open('./data/bubblesReplaySession.json', 'r') as file:
        bubblesReplaySession = file.read()
    return bubblesReplaySession


RBEmail =""
RBPassword =""
alreadyRBLoggedIn = False

@app.route('/robinhoodlogin', methods=['POST'])
def robinhoodLogin():

    global alreadyRBLoggedIn
    global RBEmail
    global RBPassword
    if (alreadyRBLoggedIn == False):
        data = request.get_json()
        RBEmail = data['RBEmail']
        RBPassword = data['RBPassword']
    # Log in to Robinhood
    rh.login(username=RBEmail, password=RBPassword, store_session=True)
    alreadyRBLoggedIn = True
    
    return 'LoggedIn'

@app.route('/buystock', methods=['POST'])
def buyStock():

    data = request.get_json()
    print(data)
    stockName = data['stockName']
    shareQuantity = float(data['shareQuantity'])
    stockPrice = float(data['stockPrice'])
    stockDeltaP = float(data['stockDeltaP'])
    limitBuyPrice = stockPrice * (1 + (stockDeltaP * 0.1))
    limitBuyPrice = round(limitBuyPrice, 2)
    print(limitBuyPrice)
    # limitSellPrice = stockPrice * (1 - (stockDeltaP * 0.1 * 0.01))
    limitSellPrice = stockPrice * (1 - 0.001)
    limitSellPrice = round(limitSellPrice, 2)

    # Log in to Robinhood
    robinhoodLogin()

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
    rh.orders.order_buy_limit(stockName, shareQuantity, limitBuyPrice, timeInForce='gfd', extendedHours=True, jsonify=True)

    return "SUCCCUSSS"

@app.route('/sellstock', methods=['POST'])
def sellStock():

    data = request.get_json()
    print(data)
    stockName = data['stockName']
    shareQuantity = float(data['shareQuantity'])
    stockPrice = float(data['stockPrice'])
    stockDeltaP = float(data['stockDeltaP'])
    limitBuyPrice = stockPrice * (1 + (stockDeltaP * 0.1))
    limitBuyPrice = round(limitBuyPrice, 2)
    # limitSellPrice = stockPrice * (1 - (stockDeltaP * 0.1 * 0.01))
    limitSellPrice = stockPrice * (1 - 0.001)
    limitSellPrice = round(limitSellPrice, 2)

    # Log in to Robinhood
    robinhoodLogin()
    rh.orders.order_sell_limit(stockName, shareQuantity, limitSellPrice, timeInForce='gfd', extendedHours=True, jsonify=True)

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


