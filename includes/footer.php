<?php /*
   Bottom of every page: footer links and the JavaScript files. A page lists its own extra scripts in
   $extraScripts (for example js/validation.js).
*/ ?>
<?php $extraScripts=$extraScripts??[]; ?>
<footer class="footer"><div class="footer-inner">
<a class="brand" href="<?= e(url('index.php')) ?>" aria-label="Coffee Talk home"><img src="<?= e(url('images/logo.png')) ?>" alt="Coffee Talk" /></a>
<span>Good coffee. Good company. © <span id="year"></span> Coffee Talk.</span>
<div class="footer-links"><a href="<?= e(url('pages/menu.php')) ?>">Order menu</a><a href="<?= e(url('index.php#visit')) ?>">Visit</a><a href="https://www.instagram.com/coffeetalk.calamba/" target="_blank" rel="noopener noreferrer">Instagram</a><a href="#">Back to top ↑</a></div>
</div></footer><script src="<?= e(url('js/main.js')) ?>"></script>
<?php /* Page-specific scripts. */ ?>
<?php foreach($extraScripts as $script): ?><script src="<?= e(url($script)) ?>"></script><?php endforeach; ?>
</body></html>
