<?php
// Reusable hero carousel partial. Expects $conn to be available.
$carousel_query = "
    SELECT carousel_id, image_url, title
    FROM hero_carousel
    WHERE is_active = 1
    ORDER BY sort_order ASC
    LIMIT 5
";

$carousel_result = $conn->query($carousel_query);
$carousel_images = [];

if ($carousel_result && $carousel_result->num_rows > 0) {
    while ($img = $carousel_result->fetch_assoc()) {
        $carousel_images[] = $img;
    }
}

if (empty($carousel_images)) {
    $carousel_images[] = [
        'image_url' => 'upload/picture/575966072_1558927741910787_5270800588714805217_n.png',
        'carousel_id' => 0,
        'title' => 'Featured'
    ];
}
?>
<div class="carousel-container">
    <div class="carousel-wrapper">
        <?php foreach ($carousel_images as $index => $slide):
            $img_url = htmlspecialchars($slide['image_url'], ENT_QUOTES, 'UTF-8');
            $title = htmlspecialchars($slide['title'] ?? 'Carousel Image', ENT_QUOTES, 'UTF-8');
            $active_class = $index === 0 ? 'active' : '';
        ?>
            <div class="carousel-slide <?php echo $active_class; ?>" data-index="<?php echo $index; ?>">
                <img src="<?php echo $img_url; ?>" alt="<?php echo $title; ?>" class="hero-img">
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const slides = document.querySelectorAll('.carousel-wrapper .carousel-slide');
    if (!slides || slides.length === 0) return;

    let current = 0;
    const INTERVAL = 4000;

    // ensure initial active state
    slides.forEach((s, i) => s.classList.toggle('active', i === 0));

    setInterval(() => {
        slides[current].classList.remove('active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('active');
    }, INTERVAL);
});
</script>
