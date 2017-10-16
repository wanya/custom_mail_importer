<?php
echo strlen('MCS7xRqDYU12a!AFemgjwnzoW0LfbJveSA2GtPBncGMjKL4OE2Uz898b7KKxiUcT9IAiUBSIhbbr8XfOYwCrgrLhrZPicfxGSgneAASLri5aqB0RmHtWBEwhLK6oM7P*5tnKMdtOak4myGnkkyvs8sXoL!*ZbchiSoqua9QZAUyzA1IpuiOAVCNoG0Zc7n4HEYmHzpVpZ7EO4rY!wgUR1w1SuH*npXWNUNKudxucJSjUOte!4nWO6J7YU1NUUGuzdsuy1xDzDnppIpFmQ8ow9CH!XefAeWq5gW60tQWBGl3U7ddZZmE89gd*0yRl07spAQaOfqq4nmEcsOZudeCx!CHsFHFSQKy9bqeucNXyDMLWfNrbSxJHnFWKhJW!ekM2npx8cBZwYhALZhymB23uog1yCTD2IEwp5x4kvxw2b95FIO61dabU5ZrMwECv*ZZQ7Kg$$');
  session_start();
  require('oauth.php');
  require('outlook.php');

  $loggedIn = !is_null($_SESSION['access_token']);
  $redirectUri = 'https://loveurmail.com/ryanburch/backend/mobileservice/msn/authorize.php';
?>

<html>
    <head>
      <title>PHP Mail API Tutorial</title>
    </head>
  <body>
    <?php 
      if (!$loggedIn) {
    ?>
      <!-- User not logged in, prompt for login -->
      <p>Please <a href="<?php echo oAuthService::getLoginUrl($redirectUri)?>">sign in</a> with your Office 365 or Outlook.com account.</p>
    <?php
      }
      else {
        $messages = OutlookService::getMessages($_SESSION['access_token'], $_SESSION['user_email']);
    ?>
      <!-- User is logged in, do something here -->
      <h2>Your messages</h2>

      <table border='1'>
        <tr>
          <th>From</th>
          <th>Subject</th>
          <th>Received</th>
        </tr>

        <?php foreach($messages['value'] as $message) { ?>
          <tr>
            <td><?php echo $message['From']['EmailAddress']['Name'] ?></td>
            <td><?php echo $message['Subject'] ?></td>
            <td><?php echo $message['ReceivedDateTime'] ?></td>
          </tr>
        <?php } ?>
      </table>
    <?php    
      }
    ?>
  </body>
</html>