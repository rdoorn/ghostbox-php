<?php global $hook, $httpRequest, $user; ?>

<!DOCTYPE html>
<html> 
 <head>
  <meta charset="UTF-8" /> <!-- html5 standard //-->
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" /> <!-- w3 standard //-->
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" /> <!-- IE9 compatibility //-->
  <link rel="stylesheet" type="text/css" href="/css/main.css">
  <?php
      // Load custom CSS according to usage
      foreach ( (array)$hook->execute('head_css_'.$httpRequest->getResource(), $httpRequest) as $header ) {
          print $header;
      }
   ?>
<!--[if lt IE 9]>
  <script src="/js/html5shiv.js"></script>
<![endif]--> 
  <script type="text/javascript" src="/js/jquery-1.11.1.min.js"></script>
  <script type="text/javascript" src="/js/jquery.awesomeCloud-0.2.js"></script>
  <script type="text/javascript" src="/js/jquery.touchSwipe.min.js"></script>
  <script type="text/javascript" src="/js/general.js"></script>
  <?php
      // Load custom Java script according to usage
      foreach ( (array)$hook->execute('head_js_'.$httpRequest->getResource(), $httpRequest) as $header ) {
          print $header;
      }
   ?>
  <link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon" />
 </head>
 <body>
  <header>
   <a class="home" href="/"><img class="left" src="/images/box.50.png">Ghostbox</a>
   <div id="header_profile">
   <?php $hook->execute('html_header', $user, $httpRequest); ?>
   </div>
  </header>
  <nav>
    <?php 
      if ($httpRequest->getResource() != null) {
        $hook->execute('html_nav_'.$httpRequest->getResource(), $httpRequest);
      } else {
        $hook->execute('html_nav', $httpRequest ); 
      }
    ?>
  </nav>
  <div id="mainwrap">
  <aside id="aside">
    <?php 
      if ($httpRequest->getResource() != null) {
        $hook->execute('html_aside_'.$httpRequest->getResource(), $httpRequest);
      } else {
        $hook->execute('html_aside', $httpRequest ); 
      }
    ?>
  </aside>
  <article id="article">
    <?php 
      if ($httpRequest->getResource() != null) {
        $hook->execute('html_article_'.$httpRequest->getResource(), $httpRequest);
      } else {
        $hook->execute('html_article', $httpRequest ); 
      }
    ?>
  </article>
  </div>
  <footer>
  <div id="error">
  </div>
  <div id="loading">
  </div>
  <div id="message">
  </div>
  <div id="toolbar">
  </div>
    <?php $hook->execute('html_footer'); ?>
    <?php $stats = new databaseHelper(); print_r($stats->getDbStatistics()) ; ?>
  </footer>
 </body>
</html>  

