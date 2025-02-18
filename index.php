<?php

  include 'DBConnection.php';

  if (isset($_COOKIE['username'])) {
    $bubblesUsername = $_COOKIE['username'];
  } 
  else {
      header("Location: /login/");
      exit();
  }

?>

<!DOCTYPE html>
<!-- UI Bubbles Page -->
  <?php
  // Your PHP logic here (e.g., database queries, variables, etc.)

  // Include the HTML template
  include 'templates/index.html';
  ?>
<!-- UI END Bubbles Page -->

<script>

  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
  }

  // Usage
  let bubblesUsername = getCookie('username');
  if (bubblesUsername) {
      // Decode the URL-encoded username
      bubblesUsername = decodeURIComponent(bubblesUsername);
    
      // Cookie is set, you can use the value of `bubblesUsername`
      console.log(`Username: ${bubblesUsername}`);
  }


  //   const width = 1920;
  //   const height = 1080;
  let width = document.querySelector(".app").clientWidth;
  let height = document.querySelector(".app").clientHeight;
  const m = [140, 20, 120, 140];
  const radius = 5;
  let timeIndex = 0; // index of array in json file for the displayed tick
  let activeStock; // the stock that was clicked on the chart
  let xForceStrength = 0.04;
  let yForceStrength = 0.99;
  let alpha = 0.3;
  let alphaDecay = 0.1;
  let decayTimeout; // reference to timeout ID for applying alpha decay
  let intervalTimer; // reference for setInterval ID so we can stop animation
  let initialFrequency = 250; // animation update frequency
  let frequency = initialFrequency; // animation update frequency
  let stopButtonHit = false;
  let updateForceTimeGraph = true;
  let stockData_forceGraph = [];
  // let frequency = 500; // animation update frequency
  let xMin = 10;
  // let xMax = 1e8; // Math.exp(xMax) is the x-axis bound
  let xMax = 1e3; // Math.exp(xMax) is the x-axis bound
  let yMin = -10; // in percent
  let yMax = 10;
  let tickinterval = 0.5; // in percent
  const x = d3.scaleLog();
  // const x = d3.scaleLinear();
  const y = d3.scaleLinear();
  const timeoutList = [];
  let animate = false;
  let tickers = [];

  const svg = d3.select("svg").attr("height", height).attr("width", width);
  let svgContainer = document.getElementById("svgGraphContainerid");

  let panelContainer = document.createElement("div");
  panelContainer.setAttribute("id", "panelContainerid");
  panelContainer.setAttribute("class", "panelContainer");
  svgContainer.append(panelContainer);
  let updateTickertextHTML_stockNameAndPrice = [];

  let selectedInfoStock = [];
  let ctrlDown = false;
  let circleDefaultColour = 'rgba(188,66,245,1)';
  // let circleSelectedColour = 'rgba(0,237,67,1)';
  let circleSelectedColour = 'rgba(30,203,225,1)';
  let circleSelectedPositive = 'rgba(0,237,67,1)';
  let circleSelectedNegative = 'rgba(245,78,66,1)';
  let circleColour5P = 'rgba(66,117,245,1)';
  let circleColour10P = 'rgba(245,197,66,1)';
  let circleColour10P_neg = 'rgba(225,30,176,1)';

  // Assuming selectedInfoStock is an array and stockName is a string variable
  window.addEventListener('keydown', function(event) {
      if (event.ctrlKey && ctrlDown != true) {
        // console.log("control down");
        ctrlDown = true;
      }
    });
    
  window.addEventListener('keyup', function(event) {
    if(event.key === "Control") {
      // console.log("control up");
      ctrlDown = false;
    }
  });

  // color gradients for traceline vectors
  const defs = svg.append("defs");
  let gradient = defs.append("linearGradient").attr("id", "green-right");
  gradient.append("stop").attr("stop-color", "rgb(6, 195, 125, 1)").attr("offset", 0).attr("stop-opacity", 0);
  gradient.append("stop").attr("stop-color", "rgb(6, 195, 125, 1)").attr("offset", 1).attr("stop-opacity", 1);
  gradient = defs.append("linearGradient").attr("id", "green-left");
  gradient.append("stop").attr("stop-color", "rgb(6, 195, 125, 1)").attr("offset", 0).attr("stop-opacity", 1);
  gradient.append("stop").attr("stop-color", "rgb(6, 195, 125, 1)").attr("offset", 1).attr("stop-opacity", 0);
  gradient = defs.append("linearGradient").attr("id", "red-right");
  gradient.append("stop").attr("stop-color", "rgb(253, 50, 55, 1)").attr("offset", 0).attr("stop-opacity", 0);
  gradient.append("stop").attr("stop-color", "rgb(253, 50, 55, 1)").attr("offset", 1).attr("stop-opacity", 1);
  gradient = defs.append("linearGradient").attr("id", "red-left");
  gradient.append("stop").attr("stop-color", "rgb(253, 50, 55, 1)").attr("offset", 0).attr("stop-opacity", 1);
  gradient.append("stop").attr("stop-color", "rgb(253, 50, 55, 1)").attr("offset", 1).attr("stop-opacity", 0);

  const slider = document.getElementById("myRange");

  const titletext = svg
    .append("g")
    .append("text")
    .attr("id", "titleTextTimeid")
    .attr("x", m[3])
    .attr("y", 100)
    .attr("font-family", "sans-serif")
    .attr("font-size", 32)
    .attr("fill", "rgb(200,200,200)");

  let tickertext = document.createElement("div");
  tickertext.setAttribute("class", "tickerInfo draggable");
  tickertext.setAttribute("draggable", "true");
  tickertext.setAttribute("style", "display: none;");
  svgContainer.prepend(tickertext);

  // open a json file, or open an API, websocket, or SSE event for streaming data

  var replayingSession = false;
  var hiJackCurrentAutoSession = false;
  var fetchDataHasRan = false
  var hasFetchDataRanAndCompleted = 0;
  var stockData = "null";
  let durationSelected = 0;

  function clearAllData() {
    replayingSession = false;
    clearInterval(intervalTimer);
    fetchDataHasRan = false
    hasFetchDataRanAndCompleted = 0;
    stockData = "null";
    timeIndex = 0;
    animate = false;

    let stockData_forceGraph = [];
    let updateTickertextHTML_stockNameAndPrice = [];
    let selectedInfoStock = [];
  }

  document.getElementById("startBubblesid").addEventListener("click", function() {
    clearAllData();
    // document.getElementById("playbackSessionid").disabled = true;
    $('#saveSessionid').attr('data-original-title', 'Save the complete session recording.');
    startTheSessionFadeOut();
    playLoadingAnimation();
    return fetchData('null', false);
  });

  async function fetchData(stockData, updatingCurrentData) {
    document.getElementById("startBubblesid").disabled = true;
    return replaySession(updatingCurrentData);
    // console.log('ever here?');
    replayingSession = false;
    return new Promise(function(resolve, reject) {
      //let data = await fetch("./data/bubbles.json");
      if (hasFetchDataRanAndCompleted == 1) {
        location.reload();
      }
      hasFetchDataRanAndCompleted = 1;
      let selectElement = document.getElementById("stockDeltaPTimespan");
      let selectedOption = selectElement.options[selectElement.selectedIndex].value;
      durationSelected = selectedOption;
      // console.log(selectedOption);
      let xhr = new XMLHttpRequest();
      if (xhr.readyState !== 0) {
        xhr.abort();
      }
      xhr.open("POST", "http://3.136.17.130:5000/bubbles_script");
      xhr.setRequestHeader("Content-Type", "application/json");
      xhr.send(JSON.stringify({ "stockDeltaPTimespan": selectedOption, stockData }));
      if (fetchDataHasRan == false) {
        $("button").prop("disabled", true);
      }
      xhr.onload = function() {
        let result = this.responseText;
        result = JSON.parse(result);
        hasFetchDataRanAndCompleted = 2;
        // document.getElementById("playbackSessionid").disabled = false;
        if (fetchDataHasRan == true) {
          resolve(result);
        }
        else {
          fetchDataHasRan = true;
          $("button").prop("disabled", false);
          document.getElementById("startBubblesid").disabled = true;
          stopLoadingAnimation();
          resolve(init(result));
        }
      };
    });
    //return await response;
  }

  async function replaySession(updatingCurrentData) {
    // replayingSession = true;
    replayingSession = false;
    hiJackCurrentAutoSession = true;
    startTheSessionFadeOut();
    // console.log("stopLoadingAnimation");
    stopLoadingAnimation();
    return new Promise(function(resolve, reject) {
      let xhr = new XMLHttpRequest();
      if (xhr.readyState !== 0) {
        xhr.abort();
      }
      xhr.open("POST", "http://3.136.17.130:5000/replaybubblessession");
      xhr.setRequestHeader("Content-Type", "application/json");
      xhr.send();
      xhr.onload = function() {
        let result = this.responseText;
        result = JSON.parse(result);
        if (updatingCurrentData == false) {
          resolve(init(result));
        } else {
          resolve(result);
        }
      };
    });
  }

  function robinhoodLogin() {
    return new Promise(function(resolve, reject) {
      let xhr = new XMLHttpRequest();
      if (xhr.readyState !== 0) {
        xhr.abort();
      }
      xhr.open("POST", "http://3.136.17.130:5000/robinhoodregister");
      xhr.setRequestHeader("Content-Type", "application/json");
      xhr.send(JSON.stringify({ "RBEmail": document.getElementById("robinhoodEmailid").value, "RBPassword": document.getElementById("robinhoodPasswordid").value, "RBTotp": document.getElementById("robinhoodTotpid").value, "bubblesUsername": bubblesUsername }));
      xhr.onload = function() {
        let result = this.responseText;
        if (result == "LoggedIn") {
          $('#robinhoodLoginModalid').modal('toggle')
        }
      };
    });
  }

  function buyStock(stockName) {
    return new Promise(function(resolve, reject) {
      let xhr = new XMLHttpRequest();
      if (xhr.readyState !== 0) {
        xhr.abort();
      }
      xhr.open("POST", "http://3.136.17.130:5000/buystock");
      xhr.setRequestHeader("Content-Type", "application/json");
      xhr.send(JSON.stringify({ "stockName": stockName, "shareQuantity": document.getElementById("shareQuantityid").value, "bubblesUsername": bubblesUsername }));
      xhr.onload = function() {
        let result = this.responseText;
      };
    });
  }

  function sellStock(stockName) {
    // // Get the value from the element
    // var shareQuantityValue = document.getElementById("shareQuantityid").value;

    // // Check if the value is a number and if it's above 0
    // if (!isNaN(shareQuantityValue) && Number(shareQuantityValue) > 0) {
    //     console.log("The value is a number and it's above 0.");
    // } else {
    //     console.log("The value is either not a number or it's not above 0.");
        
    //     return;
    // }

    return new Promise(function(resolve, reject) {
      let xhr = new XMLHttpRequest();
      if (xhr.readyState !== 0) {
        xhr.abort();
      }
      xhr.open("POST", "http://3.136.17.130:5000/sellstock");
      xhr.setRequestHeader("Content-Type", "application/json");
      xhr.send(JSON.stringify({ "stockName": stockName, "shareQuantity": document.getElementById("shareQuantityid").value, "trailingPercent": document.getElementById("trailingPercentid").value, "stoplossPrice": document.getElementById("stoplossPriceid").value, "stockPrice": parseFloat(document.getElementById("stockPriceid").innerHTML), "bubblesUsername": bubblesUsername }));
      xhr.onload = function() {
        let result = this.responseText;
      };
    });
  }

  function robinhoodHistory() {
    return new Promise(function(resolve, reject) {
      let xhr = new XMLHttpRequest();
      if (xhr.readyState !== 0) {
        xhr.abort();
      }
      xhr.open("POST", "http://3.136.17.130:5000/showrobinhoodhistory");
      xhr.setRequestHeader("Content-Type", "application/json");
      xhr.send();
      xhr.onload = function() {
        let result = this.responseText;
      };
    });
  }
  // robinhoodHistory();

  function updateStockPrices() {
    // Fetch stock names from the tickerPanelInfo class elements inside panelContainerid
    console.log(`slider.value ${slider.value}`);
    console.log(`slider.max ${slider.max}`);
    if (slider.value == slider.max && (replayingSession == false || hiJackCurrentAutoSession == true)) {
      console.log('hereee1');
      let tickers = [];
      let tickerElements = document.querySelectorAll("#panelContainerid .tickerPanelInfo");
      tickerElements.forEach(element => {
        let stockName2 = element.id.split("_")[0];
        tickers.push(stockName2);
      });

      // Send each stock name to Python script to get the latest price
      tickers.forEach(stockName => {

        const xhr = new XMLHttpRequest();
        xhr.open('POST', "http://3.136.17.130:5000/bubbles_panelsliveupdate");  // Replace with your endpoint path
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send(JSON.stringify({ "stockName": stockName }));

        xhr.onload = function() {
          if (xhr.status === 200) {
            let response = JSON.parse(xhr.responseText);
            let stockPrice = response.price;

            // Update the specific HTML element with the new stock price
            let originalPrice = parseFloat($(`#ctrlPanel_${stockName}_price`).attr('data-originalPrice'));
            let price_Ele = parseFloat(document.getElementById(`ctrlPanel_${stockName}_price`).textContent);
            let deltaP_Ele = document.getElementById(`ctrlPanel_${stockName}_deltaP`).textContent;
            deltaP_Ele = deltaP_Ele.slice(0, -1);
            let price_at630 = price_Ele/((deltaP_Ele/100)+1)
            if (price_Ele) {
              document.getElementById(`ctrlPanel_${stockName}_price`).textContent = stockPrice;
              $(`#ctrlPanel_${stockName}_deltaPSinceClick`).html(`${(((stockPrice/originalPrice)*100)-100).toFixed(2)}%`);
              if (((stockPrice/originalPrice)*100)-100 >= 0) {
                $(`#ctrlPanel_${stockName}_deltaPSinceClick`).css('background-color', '#31c700');
                $(`#${stockName}_circleid`).attr('fill', circleSelectedPositive);
              }
              else{
                $(`#ctrlPanel_${stockName}_deltaPSinceClick`).css('background-color', '#c71c00');
                $(`#${stockName}_circleid`).attr('fill', circleSelectedNegative);
              }
              $(`#ctrlPanel_${stockName}_deltaP`).html(`${(((stockPrice/price_at630)*100)-100).toFixed(2)}`);
            }
          } else {
              console.error('Error fetching stock price.');
          }
        };
      });
    }
  }
  // Set an interval to repeat the function every 15 seconds
  setInterval(updateStockPrices, 5000);


  async function init(data) {
    //let data = await fetchData();
    // console.log("data", data);

    // y-axis scale for change in price
    y.domain([yMin, yMax]).range([height - m[2], m[0]]);
    y.clamp(true);
    // x-axis scale for volume
    // console.log("init func width m[4] and m[1] "+width+" "+m[3]+" "+m[1]+" "+(width - m[1] - m[3]));
    x.domain([xMin, xMax]).range([m[3], width - m[1] - m[3]]);
    x.clamp(true);

    slider.min = 0;
    slider.max = data.length - 1;

    drawgrid(svg, x, y);

    tickers = [];
    Object.values(data[0])[0].forEach((d, i) => {
      // console.log(data[0]);
      // console.log(d)
      // console.log(i)
      tickers.push({ stock: d.stock, index: i });
    });

    svg
      .selectAll(".stock")
      .data(tickers)
      .enter()
      .append("circle")
      .attr("clicked", "no")
      .attr("class", "stock")
      // .attr("data-stockName", function (d) {
      //   const circle = d3.select(this);
      //   console.log("testingf ");
      //   return Object.values(data[timeIndex])[0][d.index]["stock"];
      // })
      .attr("id", function (d) {
        const circle = d3.select(this);
        return `${Object.values(data[timeIndex])[0][d.index]["stock"]}_circleid`;
      })
      .attr("fill", circleDefaultColour)
      .attr("stroke", "black")
      .attr("stroke-width", 1)
      .attr("r", radius);
      // .on("click", function (event) {
      //   d3.selectAll(".stock").attr("stroke", "black").attr("stroke-width", 1);
      //   const circle = d3.select(this);
      //   activeStock = d3.select(this);
      //   circle.attr("stroke", "rgba(66,245,197,1)").attr("stroke-width", 2);
      //   let deltaP = null;
      //   let deltaV = null;
      //   deltaP = Object.values(data[timeIndex])[0][circle.data()[0].index].delta_p;
      //   deltaV = Object.values(data[timeIndex])[0][circle.data()[0].index].delta_v;
      //   console.log("at click");
      //   let tickerInfoHeadText = "";
      //   tickerInfoHeadText = "Since Set Duration";
      //   updateTickertextHTML(tickerInfoHeadText, (Object.values(data[timeIndex])[0][circle.data()[0].index].stock), (deltaP.toFixed(2)), (Object.values(data[timeIndex])[0][circle.data()[0].index].price.toFixed(2)), (Object.values(data[timeIndex])[0][circle.data()[0].index].volume.toFixed(0)), (deltaV.toFixed(2)));
      //   tickertext.style.display = "block";
      // });

    var circles = svg.selectAll(".stock").data(tickers, function(d) { return d.stock; });
    circles.exit().remove();
    circles
      .attr("id", function(d) {
        return `${Object.values(data[timeIndex])[0][d.index]["stock"]}_circleid`;
      })
      .on("click", function (event) {
        d3.selectAll(".stock").attr("stroke", "black").attr("stroke-width", 1);
        const circle = d3.select(this);
        activeStock = d3.select(this);
        circle.attr("stroke", "rgba(66,245,197,1)").attr("stroke-width", 2);
        let deltaP = null;
        let deltaV = null;
        deltaP = Object.values(data[timeIndex])[0][circle.data()[0].index].delta_p;
        deltaV = Object.values(data[timeIndex])[0][circle.data()[0].index].delta_v;
        // console.log("at click");
        let tickerInfoHeadText = "";
        tickerInfoHeadText = "Stock Data";
        updateTickertextHTML(tickerInfoHeadText, (Object.values(data[timeIndex])[0][circle.data()[0].index].stock), (deltaP.toFixed(2)), (Object.values(data[timeIndex])[0][circle.data()[0].index].price), (Object.values(data[timeIndex])[0][circle.data()[0].index].volume.toFixed(0)), (deltaV.toFixed(2)), true);
        updateForceTimeGraph = true;
        tickertext.style.display = "block";
      });

    function tick() {
      // update circle positions
      d3.selectAll(".stock")
        .attr("cx", (d) => d.x)
        .attr("cy", (d) => d.y);

      // update tracelines to follow circles
      svg
        .selectAll(".traceline")
        .attr("x2", (d) => d.x)
        .attr("y2", (d) => d.y)
        .attr("stroke", (d) => {
          if (d.vy < 0) {
            if (d.vx >= 0) return "url(#green-right)";
            else return "url(#green-left)";
          } else {
            if (d.vx >= 0) return "url(#red-right)";
            else return "url(#red-left)";
          }
        });
    }

    const simulation = d3
      .forceSimulation(tickers)
      .force(
        "y",
        d3
          .forceY(function (d) {
            // console.log("stock "+Object.values(data[timeIndex])[0][d.index]["stock"])
            // console.log("delgaP "+Object.values(data[timeIndex])[0][d.index]["delta_p"])
            if (data && data[timeIndex]) {
                let values = Object.values(data[timeIndex]);
                if (values.length > 0 && d && d.index !== undefined && d.index !== null) {
                    let value = values[0][d.index];
                    if (value && value["delta_p"]) {
                        return y(value["delta_p"]);
                    } else {
                        console.log("Error: delta_p is undefined or null");
                        return y(0);
                    }
                } else {
                    console.log("Error: values is empty or d.index is undefined or null");
                    return y(0);
                }
            } else {
                console.log("Error: data[timeIndex] is undefined or null");
                return y(0);
            }
            // return y(Object.values(data[timeIndex])[0][d.index]["delta_p"]);
          })
          .strength(yForceStrength)
      )
      .force(
        "x",
        d3
          .forceX((d) => {
            if (data && data[timeIndex]) {
                let values = Object.values(data[timeIndex]);
                if (values.length > 0 && d && d.index !== undefined && d.index !== null) {
                    let value = values[0][d.index];
                    if (value && value["delta_v"]) {
                        return x(value["delta_v"]);
                    } else {
                        console.log("Error: delta_p is undefined or null");
                        return x(0);
                    }
                } else {
                    console.log("Error: values is empty or d.index is undefined or null");
                    return x(0);
                }
            } else {
                console.log("Error: data[timeIndex] is undefined or null");
                return x(0);
            }
            // return x(Object.values(data[timeIndex])[0][d.index]["delta_v"] || 1);
          })
          .strength(xForceStrength)
      )
      .force("collide", d3.forceCollide(radius))
      .alphaDecay(0)
      .alpha(alpha)
      .on("tick", tick);

    decayTimeout = setTimeout(function () {
      // console.log("init alpha decay");
      simulation.alphaDecay(0.1);
    }, 3000);

    window.addEventListener("resize", () => {
      // console.log("resize");
      width = document.querySelector(".app").clientWidth;
      height = document.querySelector(".app").clientHeight;
      svg.attr("width", width);
      svg.attr("height", height);
      y.domain([yMin, yMax]).range([height - m[2], m[0]]);
      // console.log("resize func m[4] and m[1] "+m[3]+" "+m[1]);
      x.domain([xMin, xMax]).range([m[3], width - m[1] - m[3]]);
      //tickertext.attr("x", width - m[1] - m[3]);
      drawgrid(svg, x, y);
      updateSim(simulation, data);
    });

    // Update forces when we move the slider
    // If streaming data, use a listener for SSE or Websocket events
    // slider.oninput = function () {
    //   timeIndex = parseInt(this.value);
    //   // console.log("slider "+timeIndex);
    //   updateSim(simulation, data);
    //   drawTracelines();
    //   //console.log(epochToDateTime(parseInt(Object.keys(data[timeIndex])[0])));
    //   titletext.text(epochToDateTime(parseInt(Object.keys(data[timeIndex])[0])));
    //   if (activeStock) {
    //     //tickertext.innerHTML = `${activeStock.data()[0].stock} ${Object.values(data[timeIndex])[0][activeStock.data()[0].index].delta_p.toFixed(2)}%`;
    //     let deltaP = null;
    //     let deltaV = null;
    //     deltaP = Object.values(data[timeIndex])[0][activeStock.data()[0].index].delta_p
    //     deltaV = Object.values(data[timeIndex])[0][activeStock.data()[0].index].delta_v
    //     // if (replayingSession == false) {
    //     //   deltaP = Object.values(data[timeIndex])[0][activeStock.data()[0].index].delta_p
    //     //   deltaV = Object.values(data[timeIndex])[0][activeStock.data()[0].index].delta_v
    //     // }
    //     // else {
    //     //   deltaP = (((Object.values(data[timeIndex])[0][activeStock.data()[0].index].price)/
    //     //   (Object.values(data[0])[0][activeStock.data()[0].index].price))*100)-100
    //     //   deltaV = (((Object.values(data[timeIndex])[0][activeStock.data()[0].index].volume)/
    //     //   (Object.values(data[0])[0][activeStock.data()[0].index].volume))*100)-100
    //     // }
    //     // console.log("at AS1");
    //     let tickerInfoHeadText = "";
    //     tickerInfoHeadText = "Stock Data";
    //     // if (replayingSession == false) {
    //     //   tickerInfoHeadText = "Since Last Move"
    //     // }
    //     // else {
    //     //   tickerInfoHeadText = "Since Session Start"
    //     // }
    //     updateTickertextHTML(tickerInfoHeadText, (activeStock.data()[0].stock), (deltaP.toFixed(2)), (Object.values(data[timeIndex])[0][activeStock.data()[0].index].price), (Object.values(data[timeIndex])[0][activeStock.data()[0].index].volume.toFixed(0)), (deltaV.toFixed(2)), false);
    //   }
    //   console.log('force brap hcalle');
    //   forceTimeGraph();
    // };

    if (hiJackCurrentAutoSession == true) {
      frequency = 6000;
    }
    //start animation
    // console.log("played here0");
    d3.select("#play").on("click", function () {
      // console.log("animate");
      // console.log(Object.values(data[timeIndex])[0]);
      animate = true;
      document.querySelector("#play").disabled = true;

      isIntervalRunning = true;
      intervalTimer = setInterval(async() => {
        // console.log("played here1");
        if (!isIntervalRunning) {
          return;
        }
        // console.log(frequency);
        isIntervalRunning = false;
        if (slider.value != timeIndex) {
          slider.value = timeIndex;
          slider.dispatchEvent(new Event('input'));
        }
        
        //document.getElementById("autoSetY").click();
        // console.log("sliderCurrent "+slider.value);
        // console.log("sliderMax "+slider.max);
        // if (slider.value >= slider.max) {
        //   clearInterval(intervalTimer);
        //   frequency = 10000;
        //   document.getElementById("play").click();
        // }
        // console.log("played here2");
        // console.log(replayingSession);
        // console.log("hallo 1");
        if (replayingSession == false || hiJackCurrentAutoSession == true) {
          previousDataLength = data.length;
          data = await fetchData(data, true);
          // console.log("hallo 2");
          if (data.length > previousDataLength) {
            slider.max = data.length - 1;
            updateForceTimeGraph = true;
          }
          // console.log(data);
          // console.log("played again");
        }
        
        slider.oninput = function () {
          timeIndex = parseInt(this.value);
          // console.log("slider "+timeIndex);
          updateSim(simulation, data);
          drawTracelines();
          //console.log(epochToDateTime(parseInt(Object.keys(data[timeIndex])[0])));
          titletext.text(epochToDateTime(parseInt(Object.keys(data[timeIndex])[0])));
          if (activeStock) {
            //tickertext.innerHTML = `${activeStock.data()[0].stock} ${Object.values(data[timeIndex])[0][activeStock.data()[0].index].delta_p.toFixed(2)}%`;
            let deltaP = null;
            let deltaV = null;
            deltaP = Object.values(data[timeIndex])[0][activeStock.data()[0].index].delta_p
            deltaV = Object.values(data[timeIndex])[0][activeStock.data()[0].index].delta_v
            // if (replayingSession == false) {
            //   deltaP = Object.values(data[timeIndex])[0][activeStock.data()[0].index].delta_p
            //   deltaV = Object.values(data[timeIndex])[0][activeStock.data()[0].index].delta_v
            // }
            // else {
            //   deltaP = (((Object.values(data[timeIndex])[0][activeStock.data()[0].index].price)/
            //   (Object.values(data[0])[0][activeStock.data()[0].index].price))*100)-100
            //   deltaV = (((Object.values(data[timeIndex])[0][activeStock.data()[0].index].volume)/
            //   (Object.values(data[0])[0][activeStock.data()[0].index].volume))*100)-100
            // }
            // console.log("at AS1");
            let tickerInfoHeadText = "";
            tickerInfoHeadText = "Stock Data";
            // if (replayingSession == false) {
            //   tickerInfoHeadText = "Since Last Move"
            // }
            // else {
            //   tickerInfoHeadText = "Since Session Start"
            // }
            updateTickertextHTML(tickerInfoHeadText, (activeStock.data()[0].stock), (deltaP.toFixed(2)), (Object.values(data[timeIndex])[0][activeStock.data()[0].index].price), (Object.values(data[timeIndex])[0][activeStock.data()[0].index].volume.toFixed(0)), (deltaV.toFixed(2)), false);
          }
          console.log('force brap hcalle');
          forceTimeGraph();

          if (updateForceTimeGraph == true && (document.getElementById("stockNameid") != null && document.getElementById("stockNameid").innerHTML != "")) {
            updateForceTimeGraph = false;
            document.getElementById("forceGraphContainerid").style.display = "block";
            let stockSymbol = document.getElementById("stockNameid").innerHTML;  // Change this to the stock symbol you're interested in
            stockData_forceGraph = [];

            for (let i = 0; i < data.length; i++) {
              let timestamp = Object.keys(data[i])[0];
              let stocks = Object.values(data[i])[0];
              
              for (let j = 0; j < stocks.length; j++) {
                let stock = stocks[j];
                
                if (stock.stock === stockSymbol) {
                  // We found the stock we're interested in
                  stockData_forceGraph.push({
                    timestamp: timestamp,
                    delta_p: stock.delta_p,
                    delta_v: stock.delta_v,
                    force: stock.delta_p*stock.delta_v,
                    price: stock.price
                  });
                }
              }
            }
            forceTimeGraph();
          }
          
          if (document.getElementById("stockNameid") == null || document.getElementById("stockNameid").innerHTML == "") {
            document.getElementById("forceGraphContainerid").style.display = "none";
            /* DMC style.top = "130px" to 260px */
            document.getElementById("stockTableDivid").style.top = "260px";
          }
          document.getElementById("stockTableDivid").style.top = document.getElementsByClassName("gridlines")[0].getBoundingClientRect().top+window.scrollY+"px";
          // if (replayingSession == false) {
          //   data = await replaySession();
          // }

          // console.log('SENSEI');
          if (timeIndex < data.length) {
            updateSim(simulation, data);
            //console.log("hellouuuuuuu " + parseInt(Object.keys(data[timeIndex])[0]))
            //console.log(epochToDateTime(parseInt(Object.keys(data[timeIndex])[0])));
            // console.log(Object.keys(data[timeIndex]));
            // let deltaPSorted = [...Object.values(data[timeIndex])[0]];
            // deltaPSorted.sort(function(a, b) {
            //   return Math.abs(b.delta_p) - Math.abs(a.delta_p);
            // });

            // Sort by FORCE
            // let deltaPSorted = [...Object.values(data[timeIndex])[0]];
            // deltaPSorted.sort(function(a, b) {
            //   return b.delta_v * b.delta_p - a.delta_v * a.delta_p;
            // });

            // Sort by DELTA_V
            let deltaPSorted = [...Object.values(data[timeIndex])[0]];
            deltaPSorted.sort(function(a, b) {
              return b.delta_v - a.delta_v;
            });

            // console.log(deltaPSorted);
            if (document.getElementById("stockTableDivid").style.display == "none") {
              document.getElementById("stockTableDivid").style.display = "block";
            }
            let tbody = document.getElementById("stockTableBodyid");
            tbody.innerHTML = "";
            // console.log("Heef")
            for (let i=0; i < deltaPSorted.length; i++) {
              let newRow = document.createElement("tr");
              newRow.setAttribute("id", `${deltaPSorted[i].stock}_stockTableStockid`);
              let newCell1 = document.createElement("td");
              let newCell1_button = document.createElement("button");
              newCell1.classList.add("stockTableStockNameTD");
              newCell1_button.textContent = deltaPSorted[i].stock; // Stock Name
              newCell1_button.classList.add("stockTableStockNameButton");
              newCell1.appendChild(newCell1_button);
              newRow.appendChild(newCell1);
              let newCell2 = document.createElement("td");
              newCell2.textContent = deltaPSorted[i].delta_p.toFixed(1) + "%"; // DeltaP
              newRow.appendChild(newCell2);
              let newCell3 = document.createElement("td");
              newCell3.textContent = ((deltaPSorted[i].delta_v.toFixed(2))*(deltaPSorted[i].delta_p.toFixed(2))).toFixed(0) + "x"; // DeltaV
              newRow.appendChild(newCell3);
              let newCell4 = document.createElement("td");
              newCell4.textContent = deltaPSorted[i].delta_v.toFixed(1) + "%"; // DeltaV
              newRow.appendChild(newCell4);
              let newCell5 = document.createElement("td");
              newCell5.textContent = deltaPSorted[i].price; // DeltaV
              newRow.appendChild(newCell5);
              tbody.appendChild(newRow);

              let foundStockInfo = selectedInfoStock.find(function(element) {
                return element.stock == deltaPSorted[i].stock;
              });
              if(foundStockInfo) {
                document.getElementById(`${deltaPSorted[i].stock}_stockTableStockid`).classList.add("stockTableStock_highlightCauseInSavedList");
                // console.log("hULl");
                // console.log(foundStockInfo);
                // console.log("hULl2");
                // console.log(deltaPSorted[i]);
                if (slider.value != slider.max) {
                  console.log('slider updaterrrr');
                  document.getElementById(`ctrlPanel_${deltaPSorted[i].stock}_price`).innerHTML = deltaPSorted[i].price;
                  if (((deltaPSorted[i].price/foundStockInfo.originalPrice)*100)-100 >= 0) {
                    $(`#ctrlPanel_${deltaPSorted[i].stock}_deltaPSinceClick`).css('background-color', '#31c700');
                    $(`#${deltaPSorted[i].stock}_circleid`).attr('fill', circleSelectedPositive);
                  }
                  else{
                    $(`#ctrlPanel_${deltaPSorted[i].stock}_deltaPSinceClick`).css('background-color', '#c71c00');
                    $(`#${deltaPSorted[i].stock}_circleid`).attr('fill', circleSelectedNegative);
                  }
                  // document.getElementById(`ctrlPanel_${deltaPSorted[i].stock}_deltaPSinceClick`).innerHTML = (((deltaPSorted[i].price/foundStockInfo.originalPrice)*100)-100).toFixed(2);
                  $(`#ctrlPanel_${deltaPSorted[i].stock}_deltaPSinceClick`).html(`${(((deltaPSorted[i].price/foundStockInfo.originalPrice)*100)-100).toFixed(2)}%`);
                  $(`#ctrlPanel_${deltaPSorted[i].stock}_price`).attr('data-originalPrice', foundStockInfo.originalPrice);
                  document.getElementById(`ctrlPanel_${deltaPSorted[i].stock}_deltaP`).innerHTML = (deltaPSorted[i].delta_p).toFixed(2);
                  // document.getElementById(`ctrlPanel_${deltaPSorted[i].stock}_deltaV`).innerHTML = Math.round(deltaPSorted[i].delta_v);
                }
                else {
                  updateStockPrices();
                }
              }
              else {
                document.getElementById(`${deltaPSorted[i].stock}_stockTableStockid`).classList.remove("stockTableStock_highlightCauseInSavedList");
              }
            }
            titletext.text(epochToDateTime(parseInt(Object.keys(data[timeIndex])[0])));
            if (activeStock) {
              //tickertext.innerHTML = `${activeStock.data()[0].stock} ${Object.values(data[timeIndex])[0][activeStock.data()[0].index].delta_p.toFixed(2)}%`;
              let deltaP = null;
              let deltaV = null;
              deltaP = Object.values(data[timeIndex])[0][activeStock.data()[0].index].delta_p
              deltaV = Object.values(data[timeIndex])[0][activeStock.data()[0].index].delta_v
              // if (replayingSession == false) {
              //   deltaP = Object.values(data[timeIndex])[0][activeStock.data()[0].index].delta_p
              //   deltaV = Object.values(data[timeIndex])[0][activeStock.data()[0].index].delta_v
              // }
              // else {
              //   deltaP = (((Object.values(data[timeIndex])[0][activeStock.data()[0].index].price)/
              //   (Object.values(data[0])[0][activeStock.data()[0].index].price))*100)-100
              //   deltaV = (((Object.values(data[timeIndex])[0][activeStock.data()[0].index].volume)/
              //   (Object.values(data[0])[0][activeStock.data()[0].index].volume))*100)-100
              // }
              // console.log("at AS2");
              let tickerInfoHeadText = "";
              tickerInfoHeadText = "Stock Data";
              // if (replayingSession == false) {
              //   tickerInfoHeadText = "Since Last Move"
              // }
              // else {
              //   tickerInfoHeadText = "Since Session Start"
              // }
              updateTickertextHTML(tickerInfoHeadText, (activeStock.data()[0].stock), (deltaP.toFixed(2)), (Object.values(data[timeIndex])[0][activeStock.data()[0].index].price), (Object.values(data[timeIndex])[0][activeStock.data()[0].index].volume.toFixed(0)), (deltaV.toFixed(2)), false);
            }
          }
        };

        // drawTracelines();

        if (timeIndex < data.length - 1 && stopButtonHit == false) {
          // if (document.querySelector("#play").disabled != false) {
          drawTracelines();
          timeIndex += 1;
          // }
        } 
        // else if (timeIndex >= data.length - 1 && replayingSession == true) {
        //   clearInterval(intervalTimer);
        //   document.querySelector("#play").disabled = false;
        //   animate = false;
        // }
        isIntervalRunning = true;
      }, frequency);

      // replaySession();
    });

    d3.select("#stop").on("click", function () {
      // console.log("stop");
      // animate = false;
      // document.querySelector("#play").disabled = false;
      // clearInterval(intervalTimer);
      stopButtonHit = true;
    });

    d3.select("#speedUp").on("click", function () {
      console.log("speed up");
      let tps = 1000 / frequency;
      tps = tps + 1 > 20 ? 20 : tps + 1;
      frequency = 1000 / tps;
      d3.select("#tickspersecond").text(`${tps.toFixed(0)} ticks per second`);
      console.log("frequency", frequency);
      clearInterval(intervalTimer);
      if (animate) d3.select("#play").on("click")();
    });

    d3.select("#speedDown").on("click", function () {
      console.log("speed down");
      let tps = 1000 / frequency;
      tps = tps - 1 < 1 ? 1 : tps - 1;
      frequency = 1000 / tps;
      d3.select("#tickspersecond").text(`${tps.toFixed(0)} ticks per second`);
      console.log("frequency", frequency);
      clearInterval(intervalTimer);
      if (animate) d3.select("#play").on("click")();
    });

    d3.select("#autoSetY").on("click", function () {
      autoSetY(simulation, data);
    });

    d3.select("#autoSetX").on("click", function () {
      autoSetX(simulation, data);
    });

    d3.select("#setY").on("click", function () {
      setY(simulation, data);
    });

    d3.select("#minX").on("change", function (e) {
      xMin = document.querySelector("#minX").value;
      x.domain([xMin, xMax]);
      drawgrid(svg, x, y);
      updateSim(simulation, data);
    });

    d3.select("#maxX").on("change", function (e) {
      xMax = document.querySelector("#maxX").value;
      x.domain([xMin, xMax]);
      // console.log("x grid " + x);
      drawgrid(svg, x, y);
      updateSim(simulation, data);
    });

    // console.log(';asdf');
    // document.getElementById("play").click();
    clearInterval(intervalTimer);
    d3.select("#play").on("click")();
    // if (animate) d3.select("#play").on("click")();
  }

  function drawTracelines() {
    svg.selectAll(".traceline").remove();
    svg
      .selectAll(".traceline")
      .data(tickers)
      .enter()
      // .append("line")
      .insert("line", ":first-child")
      .attr("class", "traceline")
      .attr("stroke", (d) => `url(#gradient)`)
      .attr("x1", (d) => d.x)
      .attr("x2", (d) => d.x)
      .attr("y1", (d) => d.y)
      .attr("y2", (d) => d.y)
      .attr("stroke-width", 2)
      .attr("opacity", 1)
      // .lower()
      // .transition()
      // .duration(2000)
      // .attr("opacity", 0)
      // .remove();
  }

  function autoSetY(simulation, data) {
    let thisTick = Object.values(data[timeIndex])[0];
    y.domain([d3.min(thisTick, (d) => d.delta_p), d3.max(thisTick, (d) => d.delta_p)]); // calibrate axis
    yMin = Math.round(y.domain()[0]); // in percent, so 1 percent is yMin = 1
    yMax = Math.round(y.domain()[1]);
    drawgrid(svg, x, y);
    updateSim(simulation, data);
  }

  function autoSetX(simulation, data) {
    let thisTick = Object.values(data[timeIndex])[0];
    x.domain([d3.min(thisTick, (d) => d.delta_v) || 1, d3.max(thisTick, (d) => d.delta_v)]); // calibrate axis
    xMin = Math.round(x.domain()[0]);
    xMax = Math.round(x.domain()[1]);
    drawgrid(svg, x, y);
    updateSim(simulation, data);
  }

  function setY(simulation, data) {
    yMin = parseFloat(document.querySelector("#yMin").value) || yMin;
    yMax = parseFloat(document.querySelector("#yMax").value) || yMax;
    y.domain([yMin, yMax]);
    drawgrid(svg, x, y);
    updateSim(simulation, data);
  }

  function updateSim(simulation, data) {
    simulation
      .force(
        "y",
        d3.forceY(function (d) {
          //console.log("START");
          // console.log("timeIndex "+timeIndex);
          // console.log("d.index "+d.index);
          // console.log("d.index "+d.index);
          // console.log("STock "+Object.values(data[timeIndex])[0][d.index]["stock"]);
          // console.log("delptaP y "+Object.values(data[timeIndex])[0][d.index]["delta_p"]);
          return y(Object.values(data[timeIndex])[0][d.index]["delta_p"]);
        })
      )
      .force(
        "x",
        d3.forceX((d) => {
          
          return x(Object.values(data[timeIndex])[0][d.index]["delta_v"] || 1);
          //   return x(Math.log(Object.values(data[timeIndex])[0][d.index]["volume"] || 1));
        })
      );

    let elements = document.getElementsByClassName('percentYAxis'); // replace 'p' with your specific tag
    let closestElement = null;

    for (let i = 0; i < elements.length; i++) {
        let value = parseFloat(elements[i].innerHTML.replace('%', ''));
        if (value <= 6) {
            closestElement = elements[i];
        }
        if (value <= 10) {
          P10text_YAxis = elements[i];
        }
        if (value <= -10) {
          negP10text_YAxis = elements[i];
        }
    }

    if (closestElement !== null) {
      // console.log("Closest element found: ", closestElement);
      yAxisPercent_YCoord_neg10P = negP10text_YAxis.getBoundingClientRect().top;
      yAxisPercent_YCoord_5P = closestElement.getBoundingClientRect().top;
      yAxisPercent_YCoord_10P = P10text_YAxis.getBoundingClientRect().top;
      // console.log("text position " + yAxisPercent_YCoord);

      let circleElements = document.getElementsByClassName('stock');
      for (let i = 0; i < circleElements.length; i++) {
        let circle = circleElements[i];
        // console.dir(circle);
        // console.log("cricle Y "+circle.getBoundingClientRect().top);
        // console.log("rgba "+circle.getAttribute('fill'));
        if (circle.getBoundingClientRect().top <= yAxisPercent_YCoord_10P && circle.getAttribute('fill').replace(' ','') != circleSelectedColour.replace(' ','') && circle.getAttribute('fill').replace(' ','') != circleSelectedPositive.replace(' ','') && circle.getAttribute('fill').replace(' ','') != circleSelectedNegative.replace(' ','')) {
          circle.setAttribute('fill', circleColour10P);
        } else if (circle.getBoundingClientRect().top <= yAxisPercent_YCoord_5P && circle.getAttribute('fill').replace(' ','') != circleSelectedColour.replace(' ','') && circle.getAttribute('fill').replace(' ','') != circleSelectedPositive.replace(' ','') && circle.getAttribute('fill').replace(' ','') != circleSelectedNegative.replace(' ','')) {
          circle.setAttribute('fill', circleColour5P);
        } else if (circle.getBoundingClientRect().top >= yAxisPercent_YCoord_neg10P && circle.getAttribute('fill').replace(' ','') != circleSelectedColour.replace(' ','') && circle.getAttribute('fill').replace(' ','') != circleSelectedPositive.replace(' ','') && circle.getAttribute('fill').replace(' ','') != circleSelectedNegative.replace(' ','')) {
          circle.setAttribute('fill', circleColour10P_neg);
        } else if (circle.getBoundingClientRect().top > yAxisPercent_YCoord_5P && circle.getBoundingClientRect().top < yAxisPercent_YCoord_neg10P && circle.getAttribute('fill').replace(' ','') != circleSelectedColour.replace(' ','') && circle.getAttribute('fill').replace(' ','') != circleSelectedPositive.replace(' ','') && circle.getAttribute('fill').replace(' ','') != circleSelectedNegative.replace(' ','')) {
          circle.setAttribute('fill', circleDefaultColour);
        }
      }
    } else {
      console.log("No element found");
    }

    simulation.alphaDecay(0).alpha(alpha).restart();
    clearTimeout(decayTimeout);
    decayTimeout = setTimeout(function () {
      // console.log("init alpha decay");
      simulation.alphaDecay(0.1);
    }, 3000);
  }

  function drawgrid(svg, x, y) {
    // console.log("drawgrid");
    y.nice();
    // zero line
    svg.select(".gridlines").remove();
    let g = svg.append("g").attr("class", "gridlines").lower();

    // zero percent line
    g.append("line")
      .attr("x1", 100)
      .attr("x2", width - 100)
      .attr("y1", y(0))
      .attr("y2", y(0))
      //.attr("stroke", "rgba(255,255,255,0.2")
      .attr("stroke", "rgba(255,255,255,1.0")
      .attr("stroke-dasharray", "12 4")
      .attr("stroke-width", "2");

    y.ticks().forEach((tick, i) => {
      if (tick !== 0) {
        g.append("line")
          .attr("x1", 100)
          .attr("x2", width - 100)
          .attr("y1", y(tick))
          .attr("y2", y(tick))
          //.attr("stroke", "rgba(255,255,255,0.15")
          .attr("stroke", "rgba(255,255,255,0.75")
          .attr("stroke-width", "1");
      }
      g.append("text") // left labels
        .attr("class", 'percentYAxis')
        .attr("x", 80)
        .attr("y", y(tick))
        .attr("fill", "white")
        .attr("alignment-baseline", "middle")
        .attr("text-anchor", "end")
        .text(tick + "%");
    });

    // x-grid (vertical lines)
    // number of ticks should align with the powers of 10 in the domain
    x.nice();
    x.ticks(xMax.toString().length - xMin.toString().length).forEach((tick) => {
      // console.log("tick "+tick);
      // console.log("xMax "+xMax);
      // tick += 100;
      g.append("line")
        .attr("x1", x(tick))
        .attr("x2", x(tick))
        .attr("y1", y.range()[0])
        .attr("y2", y.range()[1])
        // .attr("stroke", "rgba(0,0,0,0.15")
        .attr("stroke", "rgba(255,255,255,0.75")
        .attr("stroke-width", "1");
      g.append("text")
        .attr("x", x(tick))
        .attr("y", y.range()[0] + 30)
        .attr("fill", "white")
        .attr("text-anchor", "middle")
        .text(tick.toLocaleString("en-US"));
    });

    g.append("text")
      .attr("x", width / 2)
      .attr("y", y.range()[0] + 55)
      .attr("fill", "white")
      .attr("text-anchor", "middle")
      .attr("class", "axisTitles")
      .text("Volume (In Percent)");
  }

  function epochToDateTime(epoch) {
    let dateObj = new Date(epoch * 1000);
    let year = dateObj.getFullYear();
    let month = dateObj.toLocaleString('default', { month: 'long' });
    let monthNum = dateObj.toLocaleString('default', { month: 'numeric' });
    let day = dateObj.toLocaleString('default', { weekday: 'long' });
    let dayOfMonthNum = dateObj.toLocaleString('default', { day: 'numeric' });
    let hours = ("0" + dateObj.getHours()).slice(-2);
    let minutes = ("0" + dateObj.getMinutes()).slice(-2);
    let seconds = ("0" + dateObj.getSeconds()).slice(-2);
    let AMOrPM = dateObj.toLocaleString('default', { hour12: true, hour: 'numeric' }).slice(-2);
    let dateTime = "("+year+"/"+monthNum+"/"+dayOfMonthNum+")" + " " + day + " " + hours + ":" + minutes + ":" + seconds + " " + AMOrPM;
    return dateTime;
  }
  
  const draggable = document.querySelector('.draggable');
  let initialX, initialY, currentX, currentY;
  let xOffset = 0, yOffset = 0;
  let active = false;

  draggable.addEventListener('mousedown', dragStart);
  draggable.addEventListener('mouseup', dragEnd);
  document.addEventListener('mousemove', drag);

  function dragStart(event) {
    initialX = event.clientX - xOffset;
    initialY = event.clientY - yOffset;

    if (event.target === draggable) {
      active = true;
    }
  }

  function dragEnd() {
    initialX = currentX;
    initialY = currentY;
    active = false;
  }

  function drag(event) {
    if (active) {
      event.preventDefault();
      currentX = event.clientX - initialX;
      currentY = event.clientY - initialY;
      xOffset = currentX;
      yOffset = currentY;
      setTranslate(currentX, currentY, draggable);
    }
  }

  function setTranslate(xPos, yPos, el) {
    el.style.transform = `translate3d(${xPos}px, ${yPos}px, 0)`;
  }

  function startTheSessionFadeOut() {
    document.getElementById('sessionNotStartid').style.opacity = "0";
    document.getElementById('sessionNotStartid').style.transition = "opacity 0.8s ease-out";
  }

  function playLoadingAnimation() {
    setTimeout(() => {
      document.getElementById('loadingStocksAnimid').style = "display: block;";
      $('#loadingStocksAnimid').attr('class', 'loadingStocksAnim');
    }, 1100);
  }

  function stopLoadingAnimation() {
    setTimeout(() => {
        // console.log("stopLoadingAnimation called");
        document.getElementById('loadingStocksAnimid').style = "display: none;";
        $("#loadingStocksAnimid").removeClass("loadingStocksAnim");
    }, 2100);
  }

  let updateTickertextHTMLHasRan = false;
  function updateTickertextHTML(tickerHeadTitle, stockName, stockDeltaP, stockPrice, stockVolume, stockDeltaV, clickedStock) {
    if (updateTickertextHTMLHasRan == false) {
      tickertext.innerHTML = `<div id="tickerInfoHeadid" class="tickerInfoHead">Since Clicked: <span class="deltaPSincePress" id="percentChangeSinceClickid">0.00%</span></div>`;
      tickertext.innerHTML += `<span id="stockNameid">${stockName}</span> <span id="stockDeltaPid">${stockDeltaP}</span>%`;
      // tickertext.innerHTML += `<br>Since Clicked: <span class="deltaPSincePress" id="percentChangeSinceClickid">0</span>%`;
      tickertext.innerHTML += `<br>Price: $<span id="stockPriceid">${stockPrice}</span>`;
      tickertext.innerHTML += `<br>Volume: <span id="stockVolumeid">${stockVolume}</span>`;
      tickertext.innerHTML += `<br>DeltaV: <span id="stockDeltaVid">${Math.round(stockDeltaV)}%</span>`;
      tickertext.innerHTML += `<br><span><a class="stockLink" id="stockLinkid" href="https://robinhood.com/stocks/${stockName}" target="_blank">${stockName} On Robinhood</a></span>`;
      tickertext.innerHTML += `<br><button id="saveToSheet1stid" class="btn btn-info saveToSheetBtn" onclick="saveToSheet('${titletext.text()}','${stockName}','${stockPrice}', '1st')">Scoreboard 1st</button>`;
      tickertext.innerHTML += `<br><button id="saveToSheet2ndid" class="btn btn-info saveToSheetBtn" onclick="saveToSheet('${titletext.text()}','${stockName}','${stockPrice}', '2nd')">Scoreboard 2nd</button>`;
      tickertext.innerHTML += `<br><div id="F_xForceyTimeContainerid" class="F_xForceyTimeContainer"><span id="F_ForceDotid" class="F_ForceDot"></span></div>`;

      // function saveCurrentToSheet() {

      // }

      tickertext.innerHTML += `<br><label for"shareQuantityid" class="">Share Quantity:</label>`;
      tickertext.innerHTML += `<br><input id="shareQuantityid" name="shareQuantityid" class="shareQuantityInput" type="number" placeholder="0">`;
      tickertext.innerHTML += `<br>Buy/Sell: $<span id="shareQuantityTimesPriceid">0</span>`;
      tickertext.innerHTML += `<br><button type="button" onclick="buyStock('${stockName}')" id="buyStockid" class="btn btn-dark buySellStockInTickerInfo buyStockInTickerInfo">Buy <span id="buyStockNameid">${stockName}</span></button>`;
      tickertext.innerHTML += `<br><label for"trailingPercentid" style="font-size: 12px; margin-top: 11px;" class="">Trailing Stop Percent (can leave blank):</label>`;
      tickertext.innerHTML += `<br><span style="font-size: 18px;"><input id="trailingPercentid" name="trailingPercentid" class="sellOptionsLabels" type="number" placeholder="0">%</span>`;
      tickertext.innerHTML += `<br><label for"stoplossPriceid" style="font-size: 12px;" class="">Stoploss Price (can leave blank):</label>`;
      tickertext.innerHTML += `<br><span style="font-size: 18px;">$<input id="stoplossPriceid" name="stoplossPriceid" class="sellOptionsLabels" type="number" placeholder="0.00"></span>`;
      tickertext.innerHTML += `<button type="button" onclick="sellStock('${stockName}')" id="sellStockid" class="btn btn-dark buySellStockInTickerInfo sellStockInTickerInfo">Sell <span id="sellStockNameid">${stockName}</span></button>`;
      document.getElementById("shareQuantityid").addEventListener('input', () => {
        let inputValue = document.getElementById("shareQuantityid").value;
        let stockPrice = parseFloat(document.getElementById("stockPriceid").innerHTML);
        document.getElementById("shareQuantityTimesPriceid").innerHTML = (inputValue*stockPrice).toFixed(2);
      });
      updateTickertextHTMLHasRan = true;
      updateTickertextHTML_stockNameAndPrice = [stockName,stockPrice];
    }
    else {
      if (updateTickertextHTML_stockNameAndPrice[0] != stockName) {
        updateTickertextHTML_stockNameAndPrice = [stockName,stockPrice];
      }
      // document.getElementById("tickerInfoHeadid").innerHTML = tickerHeadTitle;
      document.getElementById("stockNameid").innerHTML = stockName;
      document.getElementById("stockDeltaPid").innerHTML = stockDeltaP;
      document.getElementById("stockPriceid").innerHTML = stockPrice;
      document.getElementById("stockVolumeid").innerHTML = stockVolume;
      document.getElementById("stockDeltaVid").innerHTML = stockDeltaV+'%';
      document.getElementById("saveToSheet1stid").onclick = function() { saveToSheet(titletext.text(), stockName, stockPrice, '1st') };
      document.getElementById("saveToSheet2ndid").onclick = function() { saveToSheet(titletext.text(), stockName, stockPrice, '2nd') };
      document.getElementById("buyStockid").onclick = function() { buyStock(stockName) };
      document.getElementById("sellStockid").onclick = function() { sellStock(stockName) };
      document.getElementById("buyStockNameid").innerHTML = stockName;
      document.getElementById("sellStockNameid").innerHTML = stockName;
      $('#F_ForceDotid').css('height', `${10+parseFloat(stockDeltaP)}px`);
      $('#F_ForceDotid').css('width', `${10+parseFloat(stockDeltaP)}px`);
      $('#F_ForceDotid').css('left', `${((slider.value/slider.max)*document.getElementById("F_xForceyTimeContainerid").offsetWidth)*0.9}px`);
      if ((parseFloat(stockDeltaP)*parseFloat(stockDeltaV))/100 < 0) {
        $('#F_ForceDotid').css('bottom', `0px`);
      } 
      else {
        $('#F_ForceDotid').css('bottom', `${(parseFloat(stockDeltaP)*parseFloat(stockDeltaV))/100}px`);
      }
      document.getElementById("shareQuantityTimesPriceid").innerHTML = ((document.getElementById("shareQuantityid").value)*stockPrice);
      $("#stockLinkid").attr('href',`https://robinhood.com/stocks/${stockName}`);
      document.getElementById("stockLinkid").innerHTML = `${stockName} On Robinhood`;
      if (((stockPrice/updateTickertextHTML_stockNameAndPrice[1])*100)-100 >= 0) {
        $('#percentChangeSinceClickid').css('background-color', '#31c700');
        // $(`#${stockName}_circleid`).attr('fill', circleSelectedPositive);
      }
      else{
        $('#percentChangeSinceClickid').css('background-color', '#c71c00');
        // $(`#${stockName}_circleid`).attr('fill', circleSelectedNegative);
      }
      $('#percentChangeSinceClickid').html(`${(((stockPrice/updateTickertextHTML_stockNameAndPrice[1])*100)-100).toFixed(2)}%`);
      // document.getElementById("buyStockNameid").innerHTML = stockName;
      // document.getElementById("sellStockNameid").innerHTML = stockName;
    }

    if (ctrlDown && clickedStock == true) {
      let foundStockInfo = selectedInfoStock.find(function(element) {
        return element.stock == stockName;
      });
      let foundStockInfo_bool = false;
      if(foundStockInfo) {
        foundStockInfo_bool = true;
      }

      if (!foundStockInfo_bool) {
        // selectedInfoStock.push(stockName);
        // selectedInfoStock_deltaP.push(stockDeltaP);
        // console.log("updatethis " + stockName);
        // console.log("control " + ctrlDown);
        let tickerInfoPanel = document.createElement("div");
        tickerInfoPanel.setAttribute("id", `${stockName}_infoPanelid`);
        tickerInfoPanel.setAttribute("class", "tickerPanelInfo");
        tickerInfoPanel.innerHTML = `<div class="tickerInfoHead">Since Saved: <span id="ctrlPanel_${stockName}_deltaPSinceClick" class="deltaPSincePress">0.00%</span></div>`;
        // tickerInfoPanel.innerHTML += `<div id="ctrlPanel_${stockName}_deltaPSinceClick" class="deltaPSincePress">0%</div>`;
        tickerInfoPanel.innerHTML += `<span class="savedTickerPanel_stockName" onclick="savedTickerStockName('${stockName}')">${stockName}</span> <span id="ctrlPanel_${stockName}_deltaP">${stockDeltaP}</span>%`;
        tickerInfoPanel.innerHTML += `<br>Price: $<span id="ctrlPanel_${stockName}_price" data-originalPrice="${stockPrice}">${stockPrice}</span>`;
        // tickerInfoPanel.innerHTML += `<br>Volume: <span>${stockVolume}</span>`;
        // tickerInfoPanel.innerHTML += `<br>DeltaV: <span id="ctrlPanel_${stockName}_deltaV">${Math.round(stockDeltaV)}</span>%`;
        tickerInfoPanel.innerHTML += `<br><span><a class="stockLink" href="https://robinhood.com/stocks/${stockName}" target="_blank">Robinhood</a></span>`;
        tickerInfoPanel.innerHTML += `<br><span class="percentChangeSinceClick"></span>`;
        document.getElementById("panelContainerid").prepend(tickerInfoPanel);
        
        $(`#${stockName}_circleid`).attr('fill', circleSelectedColour);
        selectedInfoStock.push({'stock': stockName, 'originalPrice': stockPrice, 'price': stockPrice, 'delta_p': stockDeltaP, 'delta_v': Math.round(stockDeltaV)});
        // console.log(selectedInfoStock);
      }
      else {
        // let selectedInfoStock_index = selectedInfoStock.indexOf(stockName);
        // selectedInfoStock.splice(selectedInfoStock_index, 1);
        // selectedInfoStock_deltaP.splice(selectedInfoStock_index, 1);
        selectedInfoStock = selectedInfoStock.filter(function(element) {
          return element.stock !== stockName;
        });
        let stockInfoPanel = document.getElementById(`${stockName}_infoPanelid`);
        stockInfoPanel.parentNode.removeChild(stockInfoPanel);
        $(`#${stockName}_circleid`).attr('fill', circleDefaultColour);
      }

      var tickerPanelInfoElements = document.getElementsByClassName('tickerPanelInfo');
      if (tickerPanelInfoElements.length > 4){
        document.getElementById('panelContainerid').classList.add('panelContainer_small');
        for (var i = 0; i < tickerPanelInfoElements.length; i++){
          if (tickerPanelInfoElements.length >= 9) {
            tickerPanelInfoElements[i].classList.add('tickerPanelInfo_small_9');
            tickerPanelInfoElements[i].classList.remove('tickerPanelInfo_small');
          }
          else {
            tickerPanelInfoElements[i].classList.add('tickerPanelInfo_small');
            tickerPanelInfoElements[i].classList.remove('tickerPanelInfo_small_9');
          }
        }
      } else {
        document.getElementById('panelContainerid').classList.remove('panelContainer_small');
        for (var i = 0; i < tickerPanelInfoElements.length; i++){
          tickerPanelInfoElements[i].classList.remove('tickerPanelInfo_small_9');
          tickerPanelInfoElements[i].classList.remove('tickerPanelInfo_small');
        }
      }
    }
  }

  document.getElementById("logoutid").addEventListener("click", function() {
    window.location.href = "/login/?logout";
  });
  
  $(function () {
    $('[data-toggle="tooltip"]').tooltip()
  })

  document.getElementById("saveSessionid").addEventListener("click", function() {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/session/save.php');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send();
    xhr.onload = function() {
        if (xhr.status === 200) {
            console.log(xhr.responseText);
        } else {
            console.error('Error uploading text.');
        }
        // $('#saveSessionid').attr('title', 'SESSION SAVED! It will be at the bottom of the page when you come back.');
        $('#saveSessionid').attr('data-original-title', 'SESSION SAVED! It will be at the bottom of the page when you come back.');
        // $('#saveSessionid').tooltip('hide');
        $('#saveSessionid').tooltip('show');
    };
  });
  
  $('.replaySession').click(function() {
    hiJackCurrentAutoSession = false;
    frequency = initialFrequency;
    document.getElementById("panelContainerid").innerHTML = "";
    const replaySessionidSEND = 'sessionNum='+$(this).attr('data-RSNum');
    startTheSessionFadeOut();
    playLoadingAnimation();
    return new Promise(function(resolve, reject) {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', '/session/replay.php');
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.send(replaySessionidSEND);
      xhr.onload = function() {
        if (xhr.status === 200) {
          clearAllData();
          result = JSON.parse(xhr.responseText);
          replayingSession = true;
          // document.querySelector("#play").disabled = true;

          // document.querySelector("#playbackSessionid").disabled = true;
          window.scrollTo({
            top: 0,
            left: 0,
            behavior: 'smooth'
          });
          stopLoadingAnimation();
          resolve(init(result));
        } 
        else {
          console.error('Error uploading text.');
        }
      };
    });
  });

  // $('.stockTableStockNameButton').click(function() {
  //   console.log('ELLO');
  //   console.log($(this).innerHTML);
  //   console.log('ELLO2');
  //   console.log(this.innerHTML);
  //   document.getElementById(`${$(this).innerHTML}_circleid`).click();
  // });
  $(document).ready(function(){
    $('#stockTableDivid').on('click', '.stockTableStockNameButton', function() {
      // console.log('ELLO');
      // console.log(`${this.innerHTML}_circleid`);
      let circleElement = document.getElementById(`${this.innerHTML}_circleid`);
      if (circleElement) {
        let clickEvent = new MouseEvent("click", {
          bubbles: true,
          cancelable: false,
          view: window
        });
        circleElement.dispatchEvent(clickEvent);
      } else {
        console.error(`Element with id ${circleid} does not exist.`);
      }
    });
  });

  function savedTickerStockName(savedTicker_SN) {
    console.log("savedTickerPanel_stockName");
    let circleElement = document.getElementById(`${savedTicker_SN}_circleid`);
    if (circleElement) {
      let clickEvent = new MouseEvent("click", {
        bubbles: true,
        cancelable: false,
        view: window
      });
      circleElement.dispatchEvent(clickEvent);
    } else {
      console.error(`Element with id ${circleid} does not exist.`);
    }
  }
  
  function saveToSheet(stockSheet_Time, stockSheet_StockName, stockSheet_Price, firstOrSecond) {
    if (!$("#tableSheet_Stock_Rowid td:last").length) {
      document.getElementById('tableSheet_Containerid').style = "display: block;";
    }
    $(`#tableSheet_Delete_Rowid [data-sheetstock="${stockSheet_StockName}"]`).remove();
    $("#tableSheet_Delete_Rowid").append(`<td data-sheetstock="${stockSheet_StockName}"><span class="btn btn-danger" onclick="deleteStockFromSheet('${stockSheet_StockName}')">X</span></td>`);
    $(`#tableSheet_Stock_Rowid [data-sheetstock="${stockSheet_StockName}"]`).remove();
    $("#tableSheet_Stock_Rowid").append(`<td data-sheetstock="${stockSheet_StockName}">${stockSheet_StockName}</td>`);
    $(`#tableSheet_${firstOrSecond}Price_Rowid [data-sheetstock="${stockSheet_StockName}"]`).remove();
    $(`#tableSheet_${firstOrSecond}Price_Rowid`).append(`<td data-sheetstock="${stockSheet_StockName}">${stockSheet_Price}</td>`);
    $(`#tableSheet_${firstOrSecond}Time_Rowid [data-sheetstock="${stockSheet_StockName}"]`).remove();
    stockSheet_Time = stockSheet_Time.split("day ");
    $(`#tableSheet_${firstOrSecond}Time_Rowid`).append(`<td data-sheetstock="${stockSheet_StockName}">${stockSheet_Time[1]}</td>`);
    if ($(`#tableSheet_1stPrice_Rowid [data-sheetstock="${stockSheet_StockName}"]`).length && $(`#tableSheet_2ndPrice_Rowid [data-sheetstock="${stockSheet_StockName}"]`).length) {
      let price1st = parseFloat($(`#tableSheet_1stPrice_Rowid [data-sheetstock="${stockSheet_StockName}"]`).text());
      let price2nd = parseFloat($(`#tableSheet_2ndPrice_Rowid [data-sheetstock="${stockSheet_StockName}"]`).text());
      let price1stAnd2nd_PC = ((price2nd/price1st)*100)-100; 
      $(`#tableSheet_percentChange_Rowid [data-sheetstock="${stockSheet_StockName}"]`).remove();
      $(`#tableSheet_percentChange_Rowid`).append(`<td data-sheetstock="${stockSheet_StockName}">${price1stAnd2nd_PC.toFixed(2)}%</td>`);
    }
    else {
      if ($(`#tableSheet_1stPrice_Rowid [data-sheetstock="${stockSheet_StockName}"]`).length) {
        $(`#tableSheet_2ndPrice_Rowid`).append(`<td data-sheetstock="${stockSheet_StockName}">NA</td>`);
        $(`#tableSheet_2ndTime_Rowid`).append(`<td data-sheetstock="${stockSheet_StockName}">NA</td>`);
      }
      else {
        $(`#tableSheet_1stPrice_Rowid`).append(`<td data-sheetstock="${stockSheet_StockName}">NA</td>`);
        $(`#tableSheet_1stTime_Rowid`).append(`<td data-sheetstock="${stockSheet_StockName}">NA</td>`);
      }
      $(`#tableSheet_percentChange_Rowid`).append(`<td data-sheetstock="${stockSheet_StockName}">NA</td>`);
    }
  }

  function deleteStockFromSheet(stockSheet_StockName) {
    $(`#tableSheet_Delete_Rowid [data-sheetstock="${stockSheet_StockName}"]`).remove();
    $(`#tableSheet_Stock_Rowid [data-sheetstock="${stockSheet_StockName}"]`).remove();
    $(`#tableSheet_1stPrice_Rowid [data-sheetstock="${stockSheet_StockName}"]`).remove();
    $(`#tableSheet_1stTime_Rowid [data-sheetstock="${stockSheet_StockName}"]`).remove();
    $(`#tableSheet_2ndPrice_Rowid [data-sheetstock="${stockSheet_StockName}"]`).remove();
    $(`#tableSheet_2ndTime_Rowid [data-sheetstock="${stockSheet_StockName}"]`).remove();
    $(`#tableSheet_percentChange_Rowid [data-sheetstock="${stockSheet_StockName}"]`).remove();
    if (!$("#tableSheet_Stock_Rowid td:last").length) {
      document.getElementById('tableSheet_Containerid').style = "display: none;";
    }
  };

  
  function forceTimeGraph() {

    let graphContainer = document.getElementById('forceGraphContainerid'); // Assume you have a div with id 'graph-container'
    graphContainer.innerHTML = "";
    let forceGraphStockDisplay = document.createElement('div');
    forceGraphStockDisplay.id = 'forceGraphStockDisplayid';
    forceGraphStockDisplay.textContent = "Stock: "+document.getElementById("stockNameid").innerHTML+" "+document.getElementById("titleTextTimeid").textContent;
    graphContainer.appendChild(forceGraphStockDisplay);

    let xAxisForceGraph = document.createElement('div');
    xAxisForceGraph.id = 'forceGraphContainer_xAxisid';
    graphContainer.appendChild(xAxisForceGraph);
    let yAxisForceGraph = document.createElement('div');
    yAxisForceGraph.id = 'forceGraphContainer_yAxisid';
    graphContainer.appendChild(yAxisForceGraph);
    let yAxisForceGraph_deltaV = document.createElement('div');
    yAxisForceGraph_deltaV.id = 'forceGraphContainer_yAxis_deltaVid';
    graphContainer.appendChild(yAxisForceGraph_deltaV);

    let hours = ['6:30', '6:45', '7:00', '7:15', '7:30', '7:45', '8:00', '8:15', '8:30', '8:45', '9:00', '9:15', '9:30', '9:45', '10:00', '10:15', '10:30', '10:45', '11:00', '11:15', '11:30', '11:45', '12:00', '12:15', '12:30', '12:45', '1:00'];

    hours.forEach((hour, index) => {
      let label = document.createElement('div');
      label.className = 'forceGraphXAxisLabels';
      label.style.left = (index / (hours.length - 1)) * 100 + '%';
      label.innerHTML = hour;
      xAxisForceGraph.appendChild(label);
    });

    // Normalize delta_p values so they fit in the graph
    let forceS = stockData_forceGraph.slice(0,parseInt(slider.value)+1).map(item => item.force);
    let deltaPS = stockData_forceGraph.slice(0,parseInt(slider.value)+1).map(item => item.delta_p);
    let deltaVS = stockData_forceGraph.slice(0,parseInt(slider.value)+1).map(item => item.delta_v);
    let maxForce = Math.max(...forceS);
    let minForce = Math.min(...forceS);
    let maxDeltaP = (Math.max(...deltaPS)).toFixed(2);
    let minDeltaP = (Math.min(...deltaPS)).toFixed(2);
    let maxDeltaV = (Math.max(...deltaVS)).toFixed(2);
    let minDeltaV = (Math.min(...deltaVS)).toFixed(2);

    // You might want to customize the number of ticks and format of labels according to your data and preference
    let numTicks = 5;
    let tickStep = (maxForce - minForce) / numTicks;
    let tickStep_deltaV = (maxDeltaP - minDeltaP) / numTicks;

    for(let i = 0; i <= numTicks; i++) {
      let tickDiv = document.createElement('div');
      tickDiv.className = 'forceGraphYAxisLabels';
      // tickDiv.style.bottom = (i / numTicks * 100) + '%';
      tickDiv.style.top = (i / (numTicks)) * 100 + '%';
      let tickLabel = document.createElement('span');
      tickLabel.innerText = (maxForce - i * tickStep).toFixed(0)+"x"; // Labels now start at maxForce and go down
      tickDiv.appendChild(tickLabel);
      yAxisForceGraph.appendChild(tickDiv);

      // let forceGraphYAxisDeltaVLabels = document.createElement('div');
      // forceGraphYAxisDeltaVLabels.className = 'forceGraphYAxisDeltaVLabels';
      // // forceGraphYAxisDeltaPLabels.style.bottom = (i / numTicks * 100) + '%';
      // forceGraphYAxisDeltaVLabels.style.top = (i / (numTicks)) * 100 + '%';
      // let forceGraphYAxisDeltaVLabels_label = document.createElement('span');
      // forceGraphYAxisDeltaVLabels_label.innerText = (maxDeltaV - i * tickStep_deltaV).toFixed(1) + "%"; // Labels now start at maxForce and go down
      // forceGraphYAxisDeltaVLabels.appendChild(forceGraphYAxisDeltaVLabels_label);
      // yAxisForceGraph_deltaV.appendChild(forceGraphYAxisDeltaVLabels);
    }

    let svgForce = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svgForce.setAttribute('class', 'forcegraphlines');
    svgForce.style.position = 'absolute';
    svgForce.style.top = '0';
    svgForce.style.left = '0';
    svgForce.style.width = '100%';
    svgForce.style.height = '100%';
    graphContainer.appendChild(svgForce);
    let svgDeltaP = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svgDeltaP.setAttribute('class', 'forcegraphlines');
    svgDeltaP.style.position = 'absolute';
    svgDeltaP.style.top = '0';
    svgDeltaP.style.left = '0';
    svgDeltaP.style.width = '100%';
    svgDeltaP.style.height = '100%';
    graphContainer.appendChild(svgDeltaP);
    let svgDeltaV = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svgDeltaV.setAttribute('class', 'forcegraphlines');
    svgDeltaV.style.position = 'absolute';
    svgDeltaV.style.top = '0';
    svgDeltaV.style.left = '0';
    svgDeltaV.style.width = '100%';
    svgDeltaV.style.height = '100%';
    graphContainer.appendChild(svgDeltaV);

    // Determine the position of the 0 line. This depends on the min and max forces.
    let zeroLinePosition_Force = -minForce / (maxForce - minForce) * graphContainer.offsetHeight;
    let zeroLine_Force = document.createElementNS("http://www.w3.org/2000/svg", "line");
    zeroLine_Force.setAttribute('x1', 0);
    zeroLine_Force.setAttribute('y1', graphContainer.offsetHeight - zeroLinePosition_Force); // y-coordinates are inverted
    zeroLine_Force.setAttribute('x2', graphContainer.offsetWidth);
    zeroLine_Force.setAttribute('y2', graphContainer.offsetHeight - zeroLinePosition_Force); // y-coordinates are inverted
    zeroLine_Force.setAttribute('stroke', '#de643c');
    zeroLine_Force.setAttribute('stroke-width', 2);
    svgForce.appendChild(zeroLine_Force);

    stockData_forceGraph.forEach((item, index) => {
      let pointDiv = document.createElement('div');
      pointDiv.className = 'forceGraphPoint';
      // pointDiv.style.left = (index * 10) + 'px'; // Space points out by 10 pixels
      // pointDiv.style.width = (((item.delta_p)/2).toFixed(2)+1)+"px";
      // pointDiv.style.height = (((item.delta_p)/2).toFixed(2)+1)+"px";
      if (parseInt(((item.delta_p)/2).toFixed(0)) < 1) {
        pointDiv.style.width = "3px";
        pointDiv.style.height = "3px";
      }
      else {
        pointDiv.style.width = (parseInt(((item.delta_p)/2).toFixed(0))+2)+"px";
        pointDiv.style.height = (parseInt(((item.delta_p)/2).toFixed(0))+2)+"px";
      }
      pointDiv.style.left = (index / (stockData_forceGraph.length - 1)) * 100 + '%';
      pointDiv.style.bottom = ((item.force - minForce) / (maxForce - minForce) * 100) + '%'; // Scale delta_p value to between 0 and 100%
      
      // Create a new Date object from the timestamp
      const PSTDate = new Date(item.timestamp * 1000);
      
      // // Set the title attribute for the tooltip
      // pointDiv.setAttribute('title', `Price: ${item.price}\nTime: ${PSTDate.toLocaleTimeString()}\nDelta_p: ${item.delta_p}`);
      // Add the required attributes for a Bootstrap tooltip
      pointDiv.setAttribute('data-toggle', 'tooltip');
      pointDiv.setAttribute('data-placement', 'top'); // or whatever placement you prefer
      pointDiv.setAttribute('data-forceGraphDotInfo', `Delta_P:${item.delta_p.toFixed(2)}---Price:${item.price}---Force:${(item.force).toFixed(2)}---Time::${PSTDate.toLocaleTimeString()}---Delta_V:${item.delta_v.toFixed(2)}`);
      pointDiv.addEventListener('mouseenter', show_tooltip_forceGraphDot);
      pointDiv.addEventListener('mouseleave', hide_tooltip_forceGraphDot);

      if(index < stockData_forceGraph.length - 1) { // if it's not the last point
        let nextItem = stockData_forceGraph[index + 1];
        let x1 = index / (stockData_forceGraph.length - 1) * graphContainer.offsetWidth;
        let x1_deltaP = index / (stockData_forceGraph.length - 1) * graphContainer.offsetWidth;
        let x1_deltaV = index / (stockData_forceGraph.length - 1) * graphContainer.offsetWidth;
        let y1 = (item.force - minForce) / (maxForce - minForce) * graphContainer.offsetHeight;
        let y1_deltaP = (item.delta_p.toFixed(2) - minDeltaP) / (maxDeltaP - minDeltaP) * graphContainer.offsetHeight;
        let y1_deltaV = (item.delta_v.toFixed(2) - minDeltaV) / (maxDeltaV - minDeltaV) * graphContainer.offsetHeight;
        let x2 = (index + 1) / (stockData_forceGraph.length - 1) * graphContainer.offsetWidth;
        let x2_deltaP = (index + 1) / (stockData_forceGraph.length - 1) * graphContainer.offsetWidth;
        let x2_deltaV = (index + 1) / (stockData_forceGraph.length - 1) * graphContainer.offsetWidth;
        let y2 = (nextItem.force - minForce) / (maxForce - minForce) * graphContainer.offsetHeight;
        let y2_deltaP = (nextItem.delta_p.toFixed(2) - minDeltaP) / (maxDeltaP - minDeltaP) * graphContainer.offsetHeight;
        let y2_deltaV = (nextItem.delta_v.toFixed(2) - minDeltaV) / (maxDeltaV - minDeltaV) * graphContainer.offsetHeight;

        let lineForce = document.createElementNS("http://www.w3.org/2000/svg", "line");
        lineForce.setAttribute('x1', x1);
        lineForce.setAttribute('y1', graphContainer.offsetHeight - y1); // y-coordinates are inverted
        lineForce.setAttribute('x2', x2);
        lineForce.setAttribute('y2', graphContainer.offsetHeight - y2); // y-coordinates are inverted
        lineForce.setAttribute('stroke', '#3440c2');
        lineForce.setAttribute('stroke-width', 3);

        let line_deltaP = document.createElementNS("http://www.w3.org/2000/svg", "line");
        line_deltaP.setAttribute('x1', x1_deltaP);
        line_deltaP.setAttribute('y1', graphContainer.offsetHeight - y1_deltaP); // y-coordinates are inverted
        line_deltaP.setAttribute('x2', x2_deltaP);
        line_deltaP.setAttribute('y2', graphContainer.offsetHeight - y2_deltaP); // y-coordinates are inverted
        line_deltaP.setAttribute('stroke', 'yellow');
        line_deltaP.setAttribute('stroke-width', 3);
        
        let line_deltaV = document.createElementNS("http://www.w3.org/2000/svg", "line");
        line_deltaV.setAttribute('x1', x1_deltaV);
        line_deltaV.setAttribute('y1', graphContainer.offsetHeight - y1_deltaV); // y-coordinates are inverted
        line_deltaV.setAttribute('x2', x2_deltaV);
        line_deltaV.setAttribute('y2', graphContainer.offsetHeight - y2_deltaV); // y-coordinates are inverted
        line_deltaV.setAttribute('stroke', 'lime');
        line_deltaV.setAttribute('stroke-width', 3);
        
        if (index < slider.value) {
          svgDeltaV.appendChild(line_deltaV);
          svgDeltaP.appendChild(line_deltaP);
          svgForce.appendChild(lineForce);
        }
      }
      if (index <= slider.value) {
        graphContainer.appendChild(pointDiv);
      }
    });


    // let graphContainer = document.getElementById('graph-container');  // Assume you have a div with id 'graph-container'
    // graphContainer.innerHTML = "";

    // // Normalize delta_p values so they fit in the graph
    // let deltaPs = stockData_forceGraph.map(item => item.delta_p);
    // let maxDeltaP = Math.max(...deltaPs);
    // let minDeltaP = Math.min(...deltaPs);

    // stockData_forceGraph.forEach((item, index) => {
    //   let pointDiv = document.createElement('div');
    //   pointDiv.className = 'point';
    //   pointDiv.style.left = (index * 10) + 'px';  // Space points out by 10 pixels
    //   pointDiv.style.bottom = ((item.delta_p - minDeltaP) / (maxDeltaP - minDeltaP) * 100) + '%';  // Scale delta_p value to between 0 and 100%
      
    //   graphContainer.appendChild(pointDiv);
    // });
    let tooltip_forceGraphDot = document.createElement('div');
    tooltip_forceGraphDot.setAttribute('id', "tooltip_forceGraphDotid");
    graphContainer.appendChild(tooltip_forceGraphDot);
  }


  function show_tooltip_forceGraphDot(event) {
    let data_forceGraphDot = this.getAttribute('data-forceGraphDotInfo');
    
    let tooltip_forceGraphDot = document.getElementById('tooltip_forceGraphDotid');
    tooltip_forceGraphDot.innerHTML = "Delta_P: "+data_forceGraphDot.split('---')[0].split(':')[1]+"%<br/>Delta_V: "+data_forceGraphDot.split('---')[4].split(':')[1]+"%<br/>Price: "+data_forceGraphDot.split('---')[1].split(':')[1]+"<br/>Force: "+data_forceGraphDot.split('---')[2].split(':')[1]+"<br/>Time: "+data_forceGraphDot.split('---')[3].split('::')[1];
    
    let rect_forceGraphDot = this.getBoundingClientRect();
    tooltip_forceGraphDot.style.left = (rect_forceGraphDot.right + window.scrollX + 10)-125 + 'px'; // 10 pixels to the right of the element
    tooltip_forceGraphDot.style.top = (rect_forceGraphDot.top + window.scrollY)-100 + 'px';
    
    tooltip_forceGraphDot.style.display = 'block';
  }

  function hide_tooltip_forceGraphDot() {
    let tooltip_forceGraphDot = document.getElementById('tooltip_forceGraphDotid');
    tooltip_forceGraphDot.style.display = 'none';
  }

</script>
