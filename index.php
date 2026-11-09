<?php
require __DIR__ . '/../app/web.php';
BookingService::expirePending();
$featured = array_slice(array_values(array_filter(BookingService::search(['featured' => 1]), fn($r) => in_array($r['category_slug'], ['standard', 'superieure', 'vip']))), 0, 3);
if (count($featured) < 3) $featured = array_slice(BookingService::search([]), 0, 3);
$suites = array_values(array_filter(BookingService::search(['sort' => 'price_desc']), fn($r) => str_starts_with($r['category_slug'], 'suite')));
$suites = array_slice($suites, 0, 2);
$services = all('SELECT name, description, price, icon FROM services WHERE is_active=1 ORDER BY id LIMIT 8');
$dishes = all('SELECT name, description, price FROM restaurant_products WHERE is_available=1 AND category_id IN (2,3) ORDER BY id LIMIT 4');
$gallery = all("SELECT i.path, i.media_type FROM room_images i JOIN rooms r ON r.id=i.room_id WHERE r.status<>'inactive' ORDER BY i.is_cover DESC, i.id LIMIT 9");
$reviews = all("SELECT rv.rating, rv.comment, u.first_name, u.last_name, r.name AS room FROM reviews rv JOIN customers c ON c.id=rv.customer_id JOIN users u ON u.id=c.user_id JOIN rooms r ON r.id=rv.room_id WHERE rv.is_approved=1 ORDER BY rv.rating DESC, rv.id DESC LIMIT 3");
$promos = all("SELECT name, description, code, discount_type, discount_value, end_date FROM promotions WHERE is_active=1 AND CURDATE() BETWEEN start_date AND end_date ORDER BY end_date LIMIT 3");
$stats = one("SELECT (SELECT COUNT(*) FROM rooms WHERE status<>'inactive') rooms, (SELECT ROUND(AVG(rating),1) FROM reviews WHERE is_approved=1) rating, (SELECT COUNT(*) FROM bookings WHERE status='checked_out') stays, (SELECT COUNT(*) FROM users WHERE role='customer') guests");
$heroImg = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=2000&q=80';
page_head('Accueil', ['active' => 'home', 'transparent' => true]);
?>
<section class="hero" style="background-image:url('<?= e($heroImg) ?>')">
  <canvas class="dust" data-particles aria-hidden="true"></canvas>
  <div class="container">
    <div>
      <h1>Vivez une expérience exceptionnelle.</h1>
      <p class="lead">Découvrez le confort, l'élégance et l'excellence de notre établissement.</p>
      <div class="hero-cta"><a class="btn btn-gold btn-lg" href="<?= url('/rooms.php') ?>">Découvrir nos chambres</a><a class="btn btn-ghost btn-lg" href="#apropos">Notre histoire</a></div>
    </div>
    <div class="hero3d" data-tower aria-hidden="true"></div>
  </div>
</section>
<section class="booking-bar"><div class="container">
  <form action="<?= url('/rooms.php') ?>" method="get">
    <div><label for="q_in">Date d'arrivée</label><input class="form-control" id="q_in" type="date" name="check_in" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>"></div>
    <div><label for="q_out">Date de départ</label><input class="form-control" id="q_out" type="date" name="check_out" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= date('Y-m-d', strtotime('+3 day')) ?>"></div>
    <div><label for="q_ad">Adultes</label><select class="form-select" id="q_ad" name="adults"><?php for ($i = 1; $i <= 8; $i++) echo "<option" . ($i == 2 ? ' selected' : '') . ">$i</option>"; ?></select></div>
    <div><label for="q_ch">Enfants</label><select class="form-select" id="q_ch" name="children"><?php for ($i = 0; $i <= 6; $i++) echo "<option>$i</option>"; ?></select></div>
    <div><label for="q_rm">Chambres</label><select class="form-select" id="q_rm" name="rooms"><?php for ($i = 1; $i <= 5; $i++) echo "<option>$i</option>"; ?></select></div>
    <button class="btn btn-gold btn-lg" type="submit"><i class="bi bi-search me-2"></i>Rechercher</button>
  </form>
</div></section>

<section class="section"><div class="container">
  <div class="sec-head reveal"><h2>Nos chambres populaires</h2><div class="rule"></div><p>Des espaces pensés pour votre repos, du confort essentiel au raffinement.</p></div>
  <div class="row g-4"><?php foreach ($featured as $r): ?><div class="col-md-6 col-lg-4 reveal"><?= room_card($r) ?></div><?php endforeach; ?></div>
  <div class="text-center mt-5 reveal"><a class="btn btn-outline-ink" href="<?= url('/rooms.php') ?>">Voir toutes les chambres</a></div>
</div></section>

<?php if ($suites): ?><section class="section section-dark"><div class="container">
  <div class="sec-head reveal"><h2>Nos suites de luxe</h2><div class="rule"></div><p>Espace, intimité et service sur mesure pour des séjours d'exception.</p></div>
  <div class="d-grid gap-4"><?php foreach ($suites as $s): ?>
    <div class="suite reveal"><img loading="lazy" src="<?= e(img_url($s['cover'])) ?>" alt="<?= e($s['name']) ?>">
      <div class="txt d-flex flex-column justify-content-center"><div class="room-cat"><?= e($s['category']) ?></div><h3><?= e($s['name']) ?></h3>
        <p><?= e(mb_strimwidth((string)$s['description'], 0, 150, '…')) ?></p>
        <div class="room-specs text-white-50"><span><i class="bi bi-people"></i> <?= (int)$s['capacity'] ?> pers.</span><span><i class="bi bi-aspect-ratio"></i> <?= (int)$s['size_m2'] ?> m²</span></div>
        <div class="d-flex align-items-center gap-3 flex-wrap"><div class="price"><b><?= number_format((int)$s['price_per_night'], 0, ',', ' ') ?></b> <small>FCFA / nuit</small></div><a class="btn btn-gold" href="<?= url('/room.php?id=' . $s['id']) ?>">Découvrir la suite</a></div></div></div>
  <?php endforeach; ?></div>
</div></section><?php endif; ?>

<section class="section" id="services"><div class="container">
  <div class="sec-head reveal"><h2>Services proposés</h2><div class="rule"></div><p>Tout ce qu'il faut pour que votre séjour soit simple et agréable.</p></div>
  <div class="row g-4"><?php foreach ($services as $s): ?><div class="col-sm-6 col-lg-3 reveal"><div class="flip" tabindex="0" role="group" aria-label="<?= e($s['name']) ?>"><div class="flip-in"><div class="flip-f"><i class="bi <?= e($s['icon']) ?>"></i><h5><?= e($s['name']) ?></h5></div><div class="flip-b"><p><?= e($s['description']) ?></p><div class="pr"><?= $s['price'] > 0 ? money($s['price']) : 'Inclus' ?></div></div></div></div></div><?php endforeach; ?></div>
</div></section>

<section class="section section-sand" id="restaurant"><div class="container"><div class="row g-5 align-items-center">
  <div class="col-lg-6 reveal"><img class="w-100 rounded-3" loading="lazy" style="aspect-ratio:4/3;object-fit:cover" src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1200&q=75" alt="Restaurant de l'hôtel"></div>
  <div class="col-lg-6 reveal"><h2 class="display" style="font-size:2.8rem">Restaurant</h2><p class="muted">Cuisine camerounaise authentique et classiques internationaux, préparés chaque jour avec des produits locaux. Commandez à table ou directement en chambre depuis votre espace client.</p>
    <?php foreach ($dishes as $d): ?><div class="menu-item"><div><h5><?= e($d['name']) ?></h5><p><?= e($d['description']) ?></p></div><div class="p"><?= number_format((int)$d['price'], 0, ',', ' ') ?> F</div></div><?php endforeach; ?>
    <a class="btn btn-gold mt-4" href="<?= url('/restaurant.php') ?>">Voir le menu complet</a></div>
</div></div></section>

<section class="section" id="galerie"><div class="container">
  <div class="sec-head reveal"><h2>Galerie</h2><div class="rule"></div><p>Un aperçu de nos chambres, suites et espaces.</p></div>
  <div class="ring3d reveal" data-ring aria-label="Galerie 3D : faites glisser pour tourner"><div class="ring"><?php foreach ($gallery as $g): ?><a href="<?= e(img_url($g['path'])) ?>" data-lightbox="<?= e($g['media_type']) ?>"><?php if ($g['media_type'] === 'video'): ?><video src="<?= e(img_url($g['path'])) ?>" muted playsinline preload="metadata"></video><?php else: ?><img loading="lazy" src="<?= e(img_url($g['path'])) ?>" alt="Photo de l'hôtel"><?php endif; ?></a><?php endforeach; ?></div></div>
  <div class="ring-ctl"><button type="button" data-ring-prev aria-label="Précédent">‹</button><button type="button" data-ring-next aria-label="Suivant">›</button></div>
</div></section>

<section class="section section-dark" id="apropos"><div class="container"><div class="row g-5 align-items-center">
  <div class="col-lg-6 reveal"><h2 class="display" style="font-size:3rem">À propos de l'hôtel</h2><p class="text-white-50">Au cœur de Douala, notre établissement allie le raffinement d'un hôtel international à la chaleur de l'hospitalité camerounaise. Réservation en ligne, paiement Mobile Money, accueil 24h/24 : tout est pensé pour votre tranquillité.</p><a class="btn btn-gold" href="#contact">Nous contacter</a></div>
  <div class="col-lg-6"><div class="row g-4 text-center">
    <div class="col-6 stat reveal"><b><?= (int)$stats['rooms'] ?></b>chambres et suites</div><div class="col-6 stat reveal"><b><?= $stats['rating'] ? e($stats['rating']) : '5' ?>/5</b>note moyenne</div>
    <div class="col-6 stat reveal"><b><?= (int)$stats['stays'] ?>+</b>séjours réussis</div><div class="col-6 stat reveal"><b>24h/24</b>réception ouverte</div></div></div>
</div></div></section>

<?php if ($reviews): ?><section class="section section-sand"><div class="container">
  <div class="sec-head reveal"><h2>Avis de nos clients</h2><div class="rule"></div></div>
  <div class="row g-4"><?php foreach ($reviews as $r): ?><div class="col-lg-4 reveal"><div class="review"><div class="stars mb-2"><?= str_repeat('<i class="bi bi-star-fill"></i>', (int)$r['rating']) . str_repeat('<i class="bi bi-star"></i>', 5 - (int)$r['rating']) ?></div>
    <p>« <?= e($r['comment']) ?> »</p><div class="small muted"><?= e($r['first_name'] . ' ' . mb_substr($r['last_name'], 0, 1)) ?>. · <?= e($r['room']) ?></div></div></div><?php endforeach; ?></div>
</div></section><?php endif; ?>

<?php if ($promos): ?><section class="section"><div class="container">
  <div class="sec-head reveal"><h2>Offres promotionnelles</h2><div class="rule"></div><p>Réservez maintenant et profitez de nos avantages.</p></div>
  <div class="row g-4"><?php foreach ($promos as $p): ?><div class="col-md-4 reveal"><div class="promo"><h4><?= e($p['name']) ?></h4><p class="muted"><?= e($p['description']) ?></p>
    <?php if ($p['code']): ?><span class="code"><?= e($p['code']) ?></span><?php else: ?><span class="badge-s s-confirmed">Appliquée automatiquement</span><?php endif; ?><div class="small muted mt-2">Jusqu'au <?= e(date('d/m/Y', strtotime($p['end_date']))) ?></div></div></div><?php endforeach; ?></div>
</div></section><?php endif; ?>

<section class="section section-sand" id="contact"><div class="container"><div class="row g-5">
  <div class="col-lg-6 reveal"><h2 class="display mb-3" style="font-size:2.6rem">Contact</h2><p class="muted">Une question, une demande particulière ? Écrivez-nous, nous répondons rapidement.</p>
    <form data-api="/contact" data-reset class="panel"><div class="mb-3"><label class="form-label">Nom</label><input class="form-control" name="name" required maxlength="120"></div><div class="mb-3"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" required></div>
      <div class="mb-3"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="4" required maxlength="2000"></textarea></div><button class="btn btn-gold">Envoyer le message</button></form></div>
  <div class="col-lg-6 reveal"><div class="panel mb-4"><h4>Coordonnées</h4><p class="mb-1"><i class="bi bi-geo-alt text-gold me-2"></i><?= e(setting('hotel_address')) ?></p><p class="mb-1"><i class="bi bi-telephone text-gold me-2"></i><?= e(setting('hotel_phone')) ?></p><p class="mb-0"><i class="bi bi-envelope text-gold me-2"></i><?= e(setting('hotel_email')) ?></p></div>
    <div class="panel" style="background:var(--ink);color:#fff"><h4>Newsletter</h4><p class="text-white-50">Offres exclusives et nouveautés, une fois par mois.</p>
      <form data-api="/newsletter" data-reset class="d-flex gap-2"><input class="form-control" type="email" name="email" placeholder="Votre e-mail" required aria-label="E-mail"><button class="btn btn-gold">S'abonner</button></form></div></div>
</div></div></section>

<div class="modal fade" id="lightbox" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content" style="background:transparent;box-shadow:none"><div class="modal-body p-0 text-center"><div id="lbContent"></div><button class="btn btn-gold btn-sm mt-3" data-close>Fermer</button></div></div></div></div>
<script src="<?= asset('js/home.js') ?>" defer></script>
<?php page_foot(); ?>
