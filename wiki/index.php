<?php

  require "../base.php";

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

<div class="container" style="background-color: var(--main-theme-color); width: 100%; box-sizing: border-box; padding: 1em;">
  <?php echo ParseShortLinks($conn, $htmlContent); ?>
</div>

<?php
  require "../footer.php";
?>