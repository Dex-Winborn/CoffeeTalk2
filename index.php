<?php
require_once __DIR__ . "/includes/functions.php";
$dashboard_label = is_admin() ? "Admin Dashboard" : "Staff Dashboard";
$dashboard_path = is_admin() ? "pages/admin_dashboard.php" : "pages/dashboard.php";

// Shared inline icons, printed once instead of pasted at every use.
$arrow_svg = '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 10h13m-5-5 5 5-5 5" /></svg>';
$star_svg = '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 1.5l2.5 5.4 5.9.7-4.4 4 1.2 5.8L10 14.5l-5.2 2.9 1.2-5.8-4.4-4 5.9-.7z" /></svg>';
$reviews = [
    [
        "initial" => "R",
        "name" => "Rae Daphne Mistica",
        "when" => "4 months ago",
        "text" => "I’ve been here 3-4 times already and their service, drinks and foods have always been consistently good. I absolutely love their matcha latte (as someone who rarely finds a good matcha drink) and their banana pudding! ♡ The staff are accommodating, the vibe is amazing and the cafe is cozy and air-conditioned. They also offer free Wi-Fi, restroom and parking. Definitely one of my favorite cafes in Calamba",
    ],
    [
        "initial" => "A",
        "name" => "Alec Russel Bayobo",
        "when" => "a year ago",
        "text" => "Great coffee, great service, great atmosphere overall a very great coffee shop. Highly recommended if you’re looking for a coffee shop to hangout with your friends and family.",
    ],
    [
        "initial" => "K",
        "name" => "Karl Danniel Ruby",
        "when" => "2 weeks ago",
        "text" => "Great place and nice place to work no other noise and calming.",
    ],
];
?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<meta name="theme-color" content="#002830" />
		<meta name="description" content="Coffee Talk in Calamba City — thoughtfully brewed coffee, easy conversations, and a little room to stay awhile." />
		<title>Coffee Talk — Good coffee, better conversations</title>
		<link rel="icon" type="image/png" href="images/logo.png" />
		<meta property="og:type" content="website" />
		<meta property="og:title" content="Coffee Talk — Good coffee, better conversations" />
		<meta property="og:description" content="Thoughtfully brewed coffee, easy conversations, and a little room to stay awhile in Calamba City." />
		<meta property="og:image" content="images/lobby.jpg" />
		<meta property="og:image:alt" content="The welcoming interior at Coffee Talk in Calamba City" />
		<meta name="twitter:card" content="summary_large_image" />
		<meta name="twitter:title" content="Coffee Talk — Good coffee, better conversations" />
		<meta name="twitter:description" content="Thoughtfully brewed coffee, easy conversations, and a little room to stay awhile in Calamba City." />
		<meta name="twitter:image" content="images/lobby.jpg" />
		<link rel="preconnect" href="https://fonts.googleapis.com" />
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
		<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet" />
		<link rel="stylesheet" href="css/style.css" />
	</head>
	<body>
		<div class="announcement">
			Your new favorite corner in Calamba <span>·</span> Come on in
		</div>
		<header class="site-header">
			<nav class="nav-wrap" aria-label="Main navigation">
				<a class="brand" href="#home" aria-label="Coffee Talk home">
					<img src="images/logo.png" alt="Coffee Talk" />
				</a>
				<button class="menu-toggle" type="button" aria-label="Open navigation" aria-controls="primary-navigation" aria-expanded="false">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
						<path d="M4 7h16M4 12h16M4 17h16" />
					</svg>
				</button>
				<div class="nav-links" id="primary-navigation">
					<a href="#story">Reviews</a>
					<a href="pages/menu.php">Order online</a>
					<a href="#space">The space</a>
					<?php if (is_logged_in()): ?>
						<a href="<?= e(url($dashboard_path)) ?>"><?= e($dashboard_label) ?></a>
					<?php else: ?>
						<a href="auth/login.php">Staff login</a>
					<?php endif; ?>
					<a href="#visit" class="nav-cta">Come visit <?= $arrow_svg ?></a>
				</div>
			</nav>
		</header>

		<main>
			<div class="hero-band">
				<section class="hero" id="home">
					<div class="hero-text">
						<div class="eyebrow">Coffee, conversations & calm</div>
						<h1>Brewing Bonds,<br /><em>Endless Chats</em></h1>
						<p class="hero-copy">
							A little industrial, a little retro, and all heart. Pull up a
							chair at Coffee Talk — your neighborhood spot for slow sips and
							even better company.
						</p>
						<div class="hero-actions">
							<a class="button" href="pages/menu.php">Explore the menu <?= $arrow_svg ?></a>
							<a class="text-link" href="#story">Get to know us <?= $arrow_svg ?></a>
						</div>
						<div class="hero-note">
							<span class="avatars" aria-hidden="true"><span class="avatar">☕</span><span class="avatar">☺</span><span class="avatar">✦</span></span>
							<span>A happy place for your everyday people.</span>
						</div>
					</div>
					<div class="hero-visual" aria-label="Coffee Talk lobby photo">
						<img src="images/lobby.jpg" alt="Coffee Talk lobby interior" />
					</div>
				</section>
			</div>

			<div class="ticker" aria-hidden="true">
				<div class="ticker-track">
					<span>✳</span> Good coffee, better conversations <span>✳</span> A cozy
					corner in Calamba <span>✳</span> Stay for one more cup
					<span>✳</span> Good coffee, better conversations <span>✳</span> A cozy
					corner in Calamba <span>✳</span> Stay for one more cup <span>✳</span>
				</div>
			</div>

			<section class="section" id="story">
				<div class="section-inner reviews">
					<div class="reviews-head">
						<div class="eyebrow">Customer reviews</div>
						<h2>Kind words from our regulars.</h2>
					</div>
					<div class="reviews-grid">
						<?php foreach ($reviews as $review): ?>
						<article class="review-card">
							<div class="review-top"><span class="review-avatar" aria-hidden="true"><?= e($review['initial']) ?></span><div class="review-who"><strong><?= e($review['name']) ?></strong><small>Google review</small></div></div>
							<div class="review-stars" role="img" aria-label="5 out of 5 stars">
								<?= str_repeat($star_svg, 5) ?>
								<span><?= e($review['when']) ?></span>
							</div>
							<p><?= e($review['text']) ?></p>
						</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>

			<section class="section menu-section" id="menu">
				<div class="section-inner">
					<div class="menu-heading">
						<div><div class="eyebrow">Coffee Talk Menu</div><h2>Made to be savored.</h2></div>
						<p>Coffee, non-coffee favorites, wellness blends, snacks, pasta, and croffles — with the same selections and prices from our menu.</p>
					</div>
					<?= public_menu_html() ?>
				</div>
			</section>

			<section class="section feature-section" id="highlights">
				<div class="section-inner">
					<div class="feature-header"><div class="eyebrow">Our favorites</div><h2>Signature bites & sips.</h2></div>
					<div class="feature-grid">
						<article class="feature-card"><img src="images/pasta.jpg" alt="Pasta dish at Coffee Talk" loading="lazy" /><div class="feature-copy"><span class="feature-tag">Chef favorite</span><h3>Pasta special</h3><p>Comforting, rich, and made to linger over with another round of coffee.</p></div></article>
						<article class="feature-card"><img src="images/egg-brulee.jpg" alt="Egg Coffee Brûlée and Egg Matcha Brûlée at Coffee Talk" loading="lazy" /><div class="feature-copy"><span class="feature-tag">Sweet finish</span><h3>Egg Brûlée</h3><p>A smooth, creamy favorite that turns a simple coffee break into a little ritual.</p></div></article>
					</div>
				</div>
			</section>

			<section class="section" id="space">
				<div class="section-inner space-section">
					<div class="space-copy">
						<div class="eyebrow">Stay awhile</div><h2>Your kind of place, at your own pace.</h2>
						<p>Bring a friend, your favorite playlist, or absolutely no plans. We've made a space for the good in-between moments — with a few little comforts to make it yours.</p>
						<ul class="perks">
							<li><span>🐾</span> Pet-friendly</li>
							<li><span aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M2.5 8.8a15 15 0 0 1 19 0M5.7 12a10 10 0 0 1 12.6 0m-9.4 3.2a5 5 0 0 1 6.2 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" /><circle cx="12" cy="18.5" r="1.3" fill="currentColor" /></svg></span> Free Wi-Fi</li>
							<li><span>🔌</span> Charging outlets</li><li><span>☼</span> Cozy corners</li><li><span>🚻</span> Comfort room</li><li><span>▦</span> QR payment</li><li><span>☕</span> Dine-in</li><li><span>▣</span> Take-out</li>
						</ul>
						<a class="text-link" href="#visit">Come see for yourself <?= $arrow_svg ?></a>
					</div>
				</div>
			</section>

			<section class="section visit-section" id="visit">
				<div class="section-inner visit-grid">
					<div class="visit-copy">
						<div class="eyebrow">Meet you at the café</div><h2>Find us in Calamba.</h2>
						<p>Just follow the smell of good coffee. We're on the second floor of Elfa Building, right along Chipeco Avenue.</p>
						<div class="visit-details">
							<div><h3>Come by</h3><p>Elfa Building, 2nd Floor<br />Chipeco Avenue, Calamba City<br />Laguna, Philippines</p></div>
							<div><h3>We're brewing</h3><p>Daily · Hours may vary<br />Check our socials for today's<br />latest updates.</p></div>
						</div>
						<a class="button button-light" href="https://www.google.com/maps/place/Coffee+Talk/@14.2089525,121.1609067,124m/data=!3m1!1e3!4m6!3m5!1s0x33bd63007b51f2c1:0x5ef153975a39d321!8m2!3d14.2086503!4d121.1611727!16s%2Fg%2F11xv8dvg5z?entry=ttu&g_ep=EgoyMDI2MDkzMC4wIKXMDSoASAFQAw%3D%3D" target="_blank" rel="noreferrer">Get directions <?= $arrow_svg ?></a>
					</div>
					<div class="map-card">
						<iframe title="Map showing Coffee Talk in Calamba City" src="https://maps.google.com/maps?q=14.2086503,121.1611727&z=17&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
						<span class="map-label"><strong>Coffee Talk, Calamba</strong>Elfa Building · Chipeco Avenue · Laguna</span>
					</div>
				</div>
			</section>
		</main>

		<footer class="footer">
			<div class="footer-inner">
				<a class="brand" href="#home" aria-label="Coffee Talk home"><img src="images/logo.png" alt="Coffee Talk" /></a>
				<span>Good coffee. Good company. © <span id="year"></span> Coffee Talk.</span>
				<div class="footer-links">
					<a href="#menu">Menu</a><a href="#visit">Visit</a>
					<a href="https://www.instagram.com/coffeetalk.calamba?utm_source=ig_web_button_share_sheet&stkn=ZDNlZDc0MzIxNw==" target="_blank" rel="noopener noreferrer" aria-label="Coffee Talk on Instagram">Instagram</a>
					<a href="#home">Back to top ↑</a>
				</div>
			</div>
		</footer>
		<script src="js/main.js"></script>
	</body>
</html>
