<?php
//
// ZoneMinder web error view file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <div id="page">
    <div id="header">
      <h1>ZoneMinder Error</h1>
    </div>
    <div id="content">
      <p class="error">
<?php 
global $error_message;
echo $error_message;
?>
      </p>
      <p>ZoneMinder will retry connection in <span id="countdown">30</span> seconds.</p>
      <p>
        <button id="reloadButton" type="button">Reload</button>
      </p>
    </div>
  </div>
  <script>
    document.getElementById('reloadButton').addEventListener("click", function() {
      location.reload();
    });

    var countdown = 30; // seconds
    function timerCountdown() {
      document.getElementById('countdown').innerHTML=countdown;
      countdown --;
      if ( countdown <= 0 ) {
        location.reload();
      } else {
        setTimeout(timerCountdown, 1000);
      }
    }
    setTimeout(timerCountdown, 1000);
  </script>
</body>
</html>
