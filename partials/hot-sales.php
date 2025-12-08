<?php
// Partial: Hot Sales
// Fetch hot products and render first 4 as grid, remaining as an infinite carousel
if (!isset($conn)) return;

$hot_query = "
        SELECT s.product_id, s.name, b.brand_name,
            (
             SELECT pci2.image_url
             FROM product_color_image pci2
             WHERE pci2.product_id = s.product_id
             ORDER BY pci2.sort_order ASC
             LIMIT 1
            ) AS image_url,
            (SELECT MIN(v.price) FROM product_variant v WHERE v.product_id = s.product_id) AS min_price
        FROM product s
        LEFT JOIN brand b ON s.brand_id = b.brand_id
    WHERE s.product_badge LIKE '%Hot%'
    ORDER BY s.created_at DESC
    LIMIT 4
";

$hot_res = $conn->query($hot_query);
$hot_products = [];
if ($hot_res && $hot_res->num_rows > 0) {
    while ($row = $hot_res->fetch_assoc()) {
        $hot_products[] = $row;
    }
}

if (empty($hot_products)) {
    return;
}

$trackId = 'hotTrack' . uniqid();
?>
<section class="hot-sales" role="region" aria-labelledby="hot-sales-title">
    <h2 id="hot-sales-title" class="section-title">Hot Sales</h2>
    <p class="section-subtitle">Don't miss these popular picks</p>

    <div class="hot-carousel">
        <div class="hot-carousel-viewport">
            <div class="hot-carousel-track" id="<?php echo $trackId;?>">
                <?php foreach ($hot_products as $p):
                    $pid = (int)$p['product_id'];
                    $pimg = htmlspecialchars(!empty($p['image_url']) ? $p['image_url'] : 'upload/product-image/placeholder.png', ENT_QUOTES, 'UTF-8');
                    $pname = htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8');
                    $pbrand = htmlspecialchars($p['brand_name'], ENT_QUOTES, 'UTF-8');
                    $pprice = $p['min_price'] !== null ? '₱' . number_format((float)$p['min_price'], 2) : 'Price N/A';
                ?>
                <div class="hot-card">
                    <div class="hot-badge"><i class="fa-solid fa-fire"></i></div>
                    <div class="product-image"><a href="product-detail.php?id=<?php echo $pid;?>"><img src="<?php echo $pimg;?>" alt="<?php echo $pname;?>" loading="lazy"></a></div>
                    <div class="product-name"><a href="product-detail.php?id=<?php echo $pid;?>"><?php echo $pname;?></a></div>
                    <div class="product-brand"><?php echo $pbrand;?></div>
                    <div class="product-price"><?php echo $pprice;?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <button class="hot-nav hot-prev" data-target="<?php echo $trackId;?>" aria-label="Previous" tabindex="0">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>
        <button class="hot-nav hot-next" data-target="<?php echo $trackId;?>" aria-label="Next" tabindex="0">
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
    </div>

    <script>
    (function initInfiniteHot(trackId){
        const track = document.getElementById(trackId);
        if (!track) return;
        const prev = document.querySelector('.hot-prev[data-target="'+trackId+'"]');
        const next = document.querySelector('.hot-next[data-target="'+trackId+'"]');
        // compute gap from CSS (flex gap) when possible
        const computedGap = parseFloat(getComputedStyle(track).getPropertyValue('gap')) || 14;
        const gap = computedGap;
        let isAnimating = false;

        // If there are less than 2 items, no need to init carousel
        if (track.children.length < 2) return;

        function slideNext(){
            if (isAnimating) return;
            isAnimating = true;
            const first = track.children[0];
            const cardWidth = first.getBoundingClientRect().width + gap;
            // use translate3d for GPU acceleration
            track.style.transition = 'transform 420ms cubic-bezier(.22,.98,.39,.99)';
            track.style.transform = `translate3d(-${cardWidth}px,0,0)`;
            track.addEventListener('transitionend', function handler(){
                track.removeEventListener('transitionend', handler);
                track.style.transition = 'none';
                // reset transform then move first element to end
                track.style.transform = 'translate3d(0,0,0)';
                track.appendChild(first);
                void track.offsetWidth;
                isAnimating = false;
            });
        }

        function slidePrev(){
            if (isAnimating) return;
            isAnimating = true;
            const last = track.children[track.children.length -1];
            const cardWidth = last.getBoundingClientRect().width + gap;
            track.style.transition = 'none';
            // move last to front and start from offset
            track.insertBefore(last, track.children[0]);
            track.style.transform = `translate3d(-${cardWidth}px,0,0)`;
            void track.offsetWidth;
            // animate back to 0
            track.style.transition = 'transform 420ms cubic-bezier(.22,.98,.39,.99)';
            track.style.transform = 'translate3d(0,0,0)';
            track.addEventListener('transitionend', function handler(){
                track.removeEventListener('transitionend', handler);
                track.style.transition = 'none';
                isAnimating = false;
            });
        }

        let auto = setInterval(slideNext, 3000);
        track.addEventListener('mouseenter', ()=> clearInterval(auto));
        track.addEventListener('mouseleave', ()=> { auto = setInterval(slideNext, 3000); });
        if (next) {
            next.addEventListener('click', slideNext);
            next.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); slideNext(); } });
        }
        if (prev) {
            prev.addEventListener('click', slidePrev);
            prev.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); slidePrev(); } });
        }
    })("<?php echo $trackId;?>");
    </script>
</section>
