<?php

  require_once __DIR__ . '/../../app/base.php';

  $Parsedown = new Parsedown();

  $page = $_GET['p'] ?? 'index';
  $pageFile = __DIR__ . "/pages/{$page}.md";

  if (!file_exists($pageFile)) {
      http_response_code(404);
      exit();
  }

  $content = file_get_contents($pageFile);

  $metadata = [];
  if (preg_match('/\A---\s*\R(.*?)\R---\s*\R?/s', $content, $matches)) {
      $frontMatter = $matches[1];

      foreach (preg_split('/\R/', $frontMatter) as $line) {
          if (preg_match('/^([^:]+):\s*(.*)$/', $line, $match)) {
              $key = trim($match[1]);
              $value = trim($match[2]);

              $value = trim($value, "\"'");
              $metadata[$key] = $value;
          }
      }

      $content = substr($content, strlen($matches[0]));
  }

  $htmlContent = $Parsedown->text($content);
  $PageTitle = $metadata['Title'] ?? 'Wiki';

  require "../header.php";

?>

<h1 style="margin: 0;">
  <?php echo $metadata['Title']; ?>
</h1>
<hr>
<span class="subText"><a href="https://github.com/apollo-dw/omdb/tree/master/wiki/pages" target="_blank"><i class="icon-edit"></i> Edit article</a></span>
<br><br>

<div class="container" style="background-color: var(--main-theme-color); width: 100%; box-sizing: border-box; padding: 1em;">
  <?php echo ParseShortLinks($conn, $htmlContent); ?>
</div>

<?php
  require "../footer.php";
?>
