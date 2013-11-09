<?php
define('APPROVED_ENDPOINT', TRUE);

if (!file_exists('config.php')) {
  echo 'Copy config.dist.php --> config.php and ensure appropriate values have been set.';
  exit;
}
require 'config.php';

$audio = preg_replace('/[^a-zA-Z0-9_+-]/', '', $_SERVER['PATH_INFO']);
?><html>
<head>
  <!-- required -->
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1" />

  <link rel="stylesheet" type="text/css" href="../sm2/360player.css" />

  <!-- special IE-only canvas fix -->
  <!--[if IE]><script type="text/javascript" src="../sm2/excanvas.js"></script><![endif]-->

  <!-- Apache-licensed animation library -->
  <script type="text/javascript" src="../sm2/berniecode-animator.js"></script>

  <!-- the core stuff -->
  <script type="text/javascript" src="../sm2/soundmanager2.min.js"></script>
  <script type="text/javascript" src="../sm2/360player.js"></script>

  <script type="text/javascript">
  soundManager.setup({
    // path to directory containing SM2 SWF
    url: '../sm2/'
  });
  
  threeSixtyPlayer.config.scaleFont = (navigator.userAgent.match(/msie/i)?false:true);
  threeSixtyPlayer.config.showHMSTime = true;

  // enable this in SM2 as well, as needed

  if (threeSixtyPlayer.config.useWaveformData) {
    soundManager.flash9Options.useWaveformData = true;
  }
  if (threeSixtyPlayer.config.useEQData) {
    soundManager.flash9Options.useEQData = true;
  }
  if (threeSixtyPlayer.config.usePeakData) {
    soundManager.flash9Options.usePeakData = true;
  }

  if (threeSixtyPlayer.config.useWaveformData || threeSixtyPlayer.flash9Options.useEQData || threeSixtyPlayer.flash9Options.usePeakData) {
    // even if HTML5 supports MP3, prefer flash so the visualization features can be used.
    soundManager.preferFlash = true;
  }
  
  </script>
</head>
<body>
  <div style="width: 240px; margin: 0 auto; background: #eee">
    <div class="ui360 ui360-vis">
      <a href="<?= $AUDIO_BASE_URL ?><?= $audio ?>.mp3">Voicemail message</a>
    </div>
  </div>
</body>
</html>