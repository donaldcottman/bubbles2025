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

<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script
    src="https://cdnjs.cloudflare.com/ajax/libs/d3/6.7.0/d3.js"
    integrity="sha512-+4O1tTAf2Ku73pJ0uXuoTFbXM8agSnDhmqlMylH37E1JvyLu+ZoX2Cr/E16Xljt9R9WD1tzgRXGLQPb2YT1m1A=="
    crossorigin="anonymous"
    ></script>
    <script
    src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"
    integrity="sha512-qTXRIMyZIFb8iQcfjXWCO8+M5Tbc38Qi5WzdPOYZHIlZpzBHG3L3by84BBBOiRGiEb7KKtAOAs5qYdUiZiQNNQ=="
    crossorigin="anonymous"
    ></script>

    <!-- UI ITEM Browser tab name -->
    <title>Bubbles: Test4Stonks</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/@popperjs/core@2" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="style.css" />
  </head>

  <script type='text/javascript'>
    window.smartlook||(function(d) {
      var o=smartlook=function(){ o.api.push(arguments)},h=d.getElementsByTagName('head')[0];
      var c=d.createElement('script');o.api=new Array();c.async=true;c.type='text/javascript';
      c.charset='utf-8';c.src='https://web-sdk.smartlook.com/recorder.js';h.appendChild(c);
      })(document);
      smartlook('init', '1c126a569b430af4ec6b25096d639ea43b48f583', { region: 'eu' });
  </script>
  
  <body>
    <!-- UI LoggedInAs -->
    <?php
      echo '<div style="position: absolute; right: 45px; top: 30px;">';
      echo '<div style="margin-bottom: 5px;"> Updated 2/18/2025 11:52am'</div>';
      echo '<div style="margin-bottom: 5px;"> Logged In As: ' . $bubblesUsername . '</div>';
      echo '<button type="button" id="logoutid" class="btn btn-danger" style="width: 100%">Logout</button>';
      echo '</div>';
    ?>

    <!-- UI ForceGraph -->
    <div id="forceGraphContainerid"></div>

    <!-- UI Bubbles Main -->
    <div class="app" id="appid">

      <!-- UI Stock List-->
      <div class="stockTableDiv" id="stockTableDivid" style="display: none;">
        <table class="stockTable">
          <thead>
              <tr> <th>Bubbles</th>
              <tr>
                  <th>Stock</th>
                  <th style="padding-right: 10px;">DeltaP</th>
                  <th style="padding-right: 10px;">Force</th>
                  <th style="padding-right: 10px;">DeltaV</th>
                  <th>Price</th>
              </tr>
          </thead>
          <tbody id="stockTableBodyid">
          </tbody>
        </table>
      </div>

      <!-- UI Swarmplot -->
      <div id="svgGraphContainerid" style="position: relative;">
        <div id="sessionNotStartid">START THE TEST4 SESSION</div>
        <div id="loadingStocksAnimid" style="display: none;">Retrieving All Stocks</div>
        <svg></svg>
      </div>

      <!-- UI Swarmplot slidecontrol -->
      <div class="slidecontainer">
        <input type="range" min="0" max="100" value="0" class="slider" id="myRange" />
      </div>

      <!-- UI Scoreboard -->
      <div id="tableSheet_Containerid" style="display: none;">
        <table>
          <tr id="tableSheet_Delete_Rowid">
            <th>Delete</th>
          </tr>
          <tr id="tableSheet_Stock_Rowid">
            <th>Stock</th>
          </tr>
          <tr id="tableSheet_1stPrice_Rowid">
            <th>1st Price</th>
          </tr>
          <tr id="tableSheet_1stTime_Rowid">
            <th>1st Time PST</th>
          </tr>
          <tr id="tableSheet_2ndPrice_Rowid">
            <th>2nd Price</th>
          </tr>
          <tr id="tableSheet_2ndTime_Rowid">
            <th>2nd Time PST</th>
          </tr>
          <tr id="tableSheet_percentChange_Rowid">
            <th>Percent Change</th>
          </tr>
        </table>
      </div>

      <!-- UI Start/Stop -->
      <div class="controls">
        <button type="button" id="startBubblesid" class="btn btn-dark startBubbles">Start</button>
        <div class="form-select" style="margin-left: 30px; margin: auto 2em auto 0em; display: none;">
          <select id="stockDeltaPTimespan">
              <option value="3">3 minutes</option>
              <option value="5" selected>5 minutes</option>
              <option value="10">10 minutes</option>
              <option value="25">25 minutes</option>
              <option value="40">40 minutes</option>
              <option value="60">1 hour</option>
              <option value="120">2 hours</option>
              <option value="180">3 hours</option>
              <option value="240">4 hours</option>
              <option value="600">Beginning of Day</option>
          </select>
        </div>
        <!-- <button onclick="replaySession()" type="button" id="playbackSessionid" class="btn btn-warning playAndStop" disabled>Play Back Session</button> -->
        <!-- <input id="exchangeAuthenticationid" name="exchangeAuthenticationid" class="" type="text" placeholder="012345"> -->
        <!-- <button onclick="buyStock()" type="button" class="btn btn-warning playAndStop">Buy APPL b*tch</button> -->
        <button id="play" style="display: none;">Play</button>
        <button id="stop" type="button" class="btn btn-warning playAndStop">Stop</button>
        <button id="speedDown" type="button" class="btn btn-info tickSpeedAdjust">-</button>
        <button id="speedUp" type="button" class="btn btn-info tickSpeedAdjust">+</button>
        <span id="tickspersecond">4 ticks per second</span>
        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#robinhoodLoginModalid">Robinhood Login</button>
        <button type="button" id="saveSessionid" class="btn btn-success" data-toggle="tooltip" data-placement="top" title="Save the complete session recording." style="display: none;">Save Session</button>
      </div>

      <!-- UI Robinhood Button -->
      <div class="modal fade" id="robinhoodLoginModalid" tabindex="-1" role="dialog" aria-labelledby="robinhoodLoginModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="robinhoodLoginModalLabel">Robinhood API Login</h5>
              <!-- <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button> -->
            </div>

            <!-- UI Robinhood Popup -->
            <div class="modal-body">
              <div style="padding: 0 15px 0 15px;">Login with your Robinhood credentials to Buy and Sell stocks on Bubbles.</div>
              <div class="RBLoginInputs">
                <label for="robinhoodEmailid">Robinhood Email:</label>
                <input id="robinhoodEmailid" type="text" placeholder="Email">
                <label for="robinhoodPasswordid">Robinhood Password:</label>
                <input id="robinhoodPasswordid" type="text" placeholder="Password">
                <label for="robinhoodTotpid">Robinhood 2FA Key:</label>
                <input id="robinhoodTotpid" type="text" placeholder="2FA Key">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="button" onclick="robinhoodLogin()" class="btn btn-success">Login to Robinhood API</button>
            </div>
          </div>
        </div>
      </div>

      <!-- UI Swarmplot control -->
      <div class="controls">

        <!-- UI Y-Axis control -->
        <div class="scale-input">
          <label>Min Y%:</label>
          <input type="text" id="yMin" name="yMin" />
        </div>

        <!-- UI X-Axis control -->
        <div class="scale-input">
          <label>Max Y%:</label>
          <input type="text" id="yMax" name="yMax" />
        </div>
        <button style="width: 8em" id="setY" type="button" class="btn btn-dark XYAxis">Set Y-Axis</button>
        <button style="width: 8em" id="autoSetY" type="button" class="btn btn-danger XYAxis">Auto Set Y</button>
      </div>
      <div class="controls">
        <div class="scale-input xAxisScale" style="width: 6em">
          <label>Min X:</label>
          <select name="minX" id="minX" style="width: 8em">
            <option value="1">1%</option>
            <option value="10" selected>10%</option>
            <option value="100">100%</option>
            <option value="1000">1,000%</option>
            <option value="10000">10,000%</option>
            <option value="100000">100,000%</option>
            <option value="1000000">1,000,000%</option>
            <!-- <option value="10000000">10,000,000</option>
            <option value="100000000">100,000,000</option>
            <option value="1000000000">1,000,000,000</option> -->
          </select>
        </div>
        <div class="scale-input xAxisScale" style="width: 6em">
          <label>Max X:</label>
          <select name="maxX" id="maxX" style="width: 8em">
            <option value="1">1%</option>
            <option value="10">10%</option>
            <option value="100">100%</option>
            <option value="200">200%</option>
            <option value="500">500%</option>
            <option value="1000" selected>1,000%</option>
            <option value="10000">10,000%</option>
            <option value="100000">100,000%</option>
            <option value="1000000">1,000,000%</option>
            <!-- <option value="10000000">10,000,000</option>
            <option value="100000000" selected>100,000,000</option>
            <option value="1000000000">1,000,000,000</option> -->
          </select>
        </div>
        <button style="width: 8em" id="autoSetX" type="button" class="btn btn-danger XYAxis">Auto Set X</button>
      </div>

      <!-- UI Archive list -->
      <?php
        // $sql = "SELECT * FROM replaysessions WHERE username = ? ORDER BY count DESC";
        $sql = "SELECT username, duration, timestamp, count FROM replaysessions ORDER BY count DESC";
        $stmt = $conn->prepare($sql);
        // $stmt->bind_param();
        $stmt->execute();    
        echo '<div class="replaySessionsContainer">';
        echo '<div class="replaySessionsTitle">Archived Sessions Test4</div>';
        echo '<div class="replaySessionsHeader">
                <div>Timestamp</div>
                <div>Session</div>
                <div>DeltaP Duration</div>
                <div>Username</div>
              </div>';
        date_default_timezone_set('America/Los_Angeles');
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {

          $from_timezone = new DateTimeZone('UTC'); // Use UTC timezone for Unix timestamp
          $to_timezone = new DateTimeZone(date_default_timezone_get()); // Use local timezone of the client
          $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $row["timestamp"], $from_timezone);
          $datetime->setTimezone($to_timezone);
          $formatted_date = $datetime->format('F j, Y g:i a');

          echo "<div class='replaySessionLine'><div class='replaySessionTimestamp'> " . $formatted_date . " </div><div><button class='replaySession' data-RSNum='" . $row["count"] . "'> Replay Session </button></div>" . "<div class='replaySessionArchiveDuration'>" . $row["duration"] . "</div>" . " <div class='replaySessionUsername'>" . $row["username"] . "</div></div>";
        }
        echo '</div>';

        $stmt->close();
        $conn->close();
      ?>
      <!-- UI END Archive list -->
      <div>&nbsp;</div>
    </div>
  </body>
  <!-- UI END Bubbles Main -->
</html>