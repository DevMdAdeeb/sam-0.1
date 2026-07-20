<?php
include 'include/db.php';
// فحص وضع الصيانة
if ($isMaintenance == 1) {
    die(' <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>المتجر تحت الصيانة</title>
        <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&display=swap" rel="stylesheet">
        <style>
            body {
                margin: 0; padding: 0;
                font-family: "Almarai", sans-serif;
                background-color: #f4f6f8;
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                color: #333;
            }
            .maintenance-card {
                background: #fff;
                width: 70%;
                max-width: 450px;
                padding: 40px 20px;
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                text-align: center;
                border-top: 6px solid #1a2a3a;
            }
            .icon { font-size: 4rem; margin-bottom: 20px; animation: float 3s ease-in-out infinite; }
            h1 { font-size: 1.5rem; color: #1a2a3a; margin: 0 0 10px; font-weight: 800; }
            p { color: #666; line-height: 1.6; margin-bottom: 30px; font-size: 0.95rem; }
            .btn-contact {
                display: inline-block; text-decoration: none;
                background-color: #25d366; color: #fff;
                padding: 12px 30px; border-radius: 50px;
                font-weight: bold; box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
            }
            @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-10px); } 100% { transform: translateY(0px); } }
        </style>
    </head>
    <body>
        <div class="maintenance-card">
            <div class="icon">🛠️</div>
            <h1>نعود قريباً!</h1>
            <p>نقوم حالياً بتحديث البضاعة وإجراء بعض التحسينات في المتجر لخدمتكم بشكل أفضل.<br>شكراً لصبركم.</p>
            <a href="https://wa.me/' . $sitePhone . '" class="btn-contact">تواصل معنا واتساب</a>
        </div>
    </body>
    </html>');
}
$favMap = [];
if(isset($user_session)) {
    $fStmt = $pdo->prepare("SELECT product_id FROM favorites WHERE session_id = ?");
    $fStmt->execute([$user_session]);
    while ($fRow = $fStmt->fetch(PDO::FETCH_ASSOC)) {
        $favMap[$fRow['product_id']] = true;
    }
}
// =========================================================
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> متجر سام </title>
    <link rel="icon" type="image/x-icon" href="icons/icon-192x192.png">
    <link rel="stylesheet" href="style.min.css?v=19.0">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <!-- ================= قسم العروض (مع الوصف والكمية) ================= -->
<style>
    .offers-section-final {
        margin: 20px 0;
        direction: ltr; 
        position: relative;
    }

    .offers-container-final {
        display: flex;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        gap: 15px;
        padding: 20px 0;
        scrollbar-width: none; 
        padding-left: calc(50% - 140px); 
        padding-right: calc(50% - 140px);
    }
    .offers-container-final::-webkit-scrollbar { display: none; }

    .offer-card-final {
        flex: 0 0 280px;
        width: 280px;
        height: 400px; /* زيادة الطول قليلاً لاستيعاب الوصف */
        
        background: #fff;
        border: 2px solid #e0e0e0;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        
        scroll-snap-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        padding: 15px;
        box-sizing: border-box;
        position: relative;
        
        opacity: 1;
        filter: none;
        transform: scale(1); 
        transition: transform 0.3s ease;
    }

    .offer-card-final:active { transform: scale(0.98); }

    .offer-img-final {
        width: 100%;
        height: 150px;
        object-fit: contain;
        margin-bottom: 5px;
    }

    .offer-details-final {
        width: 100%;
        text-align: center;
        direction: rtl; 
    }

    .offer-badge-final {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #d00000;
        color: #fff;
        font-size: 0.85rem;
        padding: 5px 12px;
        border-radius: 5px;
        font-weight: bold;
        z-index: 5;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .offer-title-final {
        font-size: 1.1rem;
        font-weight: 800;
        color: #1a2a3a;
        margin: 5px 0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* تنسيق الوصف الجديد */
    .offer-desc-final {
        font-size: 0.8rem;
        color: #777;
        margin: 0 0 8px 0;
        line-height: 1.4;
        height: 2.8em; /* ارتفاع لسطرين */
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    /* تنسيق الكمية الجديد */
    .offer-qty-final {
        font-size: 0.75rem;
        color: #28a745; /* أخضر */
        background: #e8f5e9;
        padding: 3px 10px;
        border-radius: 10px;
        display: inline-block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .btn-offer-final {
        background: #1a2a3a;
        color: #fff;
        border: none;
        padding: 10px;
        border-radius: 8px;
        width: 100%;
        font-weight: bold;
        cursor: pointer;
        font-family:'Cairo',sans-serif
        font-size: 1rem;
        margin-top: 5px;
    }
</style>
</head>
<body>

    <header>
        <!-- رابط الشعار يعود للصفحة الرئيسية -->
        <a href="index.php" class="logo-container">
            <!-- الكلمة الثانية (الذهبية/الرفيعة) -->
            <span class="logo-text-store"><?= $siteName2 ?></span>
            
            <!-- الكلمة الأولى (الكحلية/العريضة) -->
            <span class="logo-text-sam"><?= $siteName1 ?></span>
        </a>
        <!-- <img src="sam.png" alt="شعار" style="max-height:50px;"> -->
        <!-- <h1>متجر سام</h1> -->
          <form action="search.php" method="GET" class="search-container">
            <input type="text" name="q" class="search-input" placeholder="ابحث عن منتج..." required>
            <button type="submit" class="search-btn"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
  <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
</svg></button>
        </form>
    </header>

    <!-- <div class="marquee-container">
        <div class="marquee">أهلاً بكم في متجر سام.. تسوق ممتع وعروض مميزة!</div>
    </div> -->
<!-- ================= قسم العروض الخاصة ================= -->

<?php
$offerSql = "SELECT p.*, (SELECT COALESCE(SUM(qty), 0) FROM cart WHERE product_id = p.id) as total_reserved FROM products p WHERE discount_price IS NOT NULL AND discount_price > 0 AND (quantity > 0 OR quantity IS NULL) ORDER BY RAND() LIMIT 5";
$offerStmt = $pdo->query($offerSql);
$offers = $offerStmt->fetchAll(PDO::FETCH_ASSOC);

if (count($offers) > 0): 
?>
<div class="offers-section-final">
    <div class="offers-container-final" id="finalSlider">
        <?php foreach($offers as $off): 
            $percent = 0;
            if($off['price'] > 0) $percent = round((($off['price'] - $off['discount_price']) / $off['price']) * 100);
            
            if ($off['quantity'] !== null) {
                $realQty = $off['quantity'] - $off['total_reserved'];
                if ($realQty <= 0) continue;
            } else { $realQty = null; }
        ?>
        <div class="offer-card-final">
            <span class="offer-badge-final"> % <?= $percent ?> خصم </span>
            <img src="uploads/<?= $off['image'] ?>" class="offer-img-final" alt="<?= $off['name'] ?>">
            
            <div class="offer-details-final">
                <h3 class="offer-title-final"><?= $off['name'] ?></h3>
                
                <!-- إضافة الوصف هنا -->
                <p class="offer-desc-final"><?= htmlspecialchars($off['description'] ?? '') ?></p>
                
                <div style="margin-bottom: 5px;">
                    <span style="text-decoration:line-through; color:#999; font-size:0.9rem;"><?= $off['price'] ?></span>
                    <span style="color:#d00000; font-weight:900; font-size:1.3rem; margin-right:5px;"><?= $off['discount_price'] ?> ر.ي</span>
                </div>

                <!-- إضافة الكمية هنا -->
                <?php if ($realQty !== null): ?>
                    <div class="offer-qty-final">
                        متبقي: <span id="qty_off_<?= $off['id'] ?>"><?= $realQty ?></span>
                    </div>
                <?php else: ?>
                    <div style="height:24px;"></div> <!-- مسافة فارغة للحفاظ على الشكل -->
                <?php endif; ?>

                <button class="btn-offer-final" onclick="checkSizeAndAdd(<?= $off['id'] ?>, <?= $realQty !== null ? 'true' : 'false' ?>, 'qty_off_<?= $off['id'] ?>', '<?= htmlspecialchars($off['sizes'] ?? '', ENT_QUOTES) ?>')">
                    أضف للسلة 🛒
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    // سكربت التحريك البسيط (بدون حسابات معقدة)
    document.addEventListener("DOMContentLoaded", function() {
        const slider = document.getElementById('finalSlider');
        
        if(slider) {
            setInterval(() => {
                // عرض البطاقة + الفراغ (280 + 15)
                const scrollAmount = 295; 
                
                // إذا وصلنا للنهاية نعود للبداية
                if (slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 50) {
                    slider.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    // التحريك لليسار (لأن الاتجاه LTR)
                    slider.scrollBy({ left: scrollAmount, behavior: 'smooth' });
                }
            }, 7000); // 15 ثانية
        }
    });
</script>
<?php endif; ?>
<!-- =============== : العروضات=============== -->
    <div class="container">

        <!-- ================= قسم أحدث المنتجات ================= -->
        <?php
        ob_start();
        $sqlLatest = "SELECT p.*, 
                      (SELECT COALESCE(SUM(qty), 0) FROM cart WHERE product_id = p.id) as total_reserved
                      FROM products p
                      INNER JOIN (
                          SELECT MAX(id) as max_id
                          FROM products
                          WHERE (quantity > 0 OR quantity IS NULL)
                          GROUP BY category_id
                      ) as latest_per_cat ON p.id = latest_per_cat.max_id
                      ORDER BY p.created_at DESC 
                      LIMIT 10";
        $stmt = $pdo->query($sqlLatest);
        $hasLatest = false;

        while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
            // حساب الكمية
            if ($row['quantity'] !== null) {
                $realQty = $row['quantity'] - $row['total_reserved'];
                if ($realQty <= 0) continue;
            } else {
                $realQty = null;
            }
            $hasLatest = true;
            $price = $row['discount_price'] ? $row['discount_price'] : $row['price'];
        ?>
            <div class="product-card">
                <!-- زر المفضلة (أحدث المنتجات) -->
                <button class="fav-btn <?= isset($favMap[$row['id']]) ? 'active' : '' ?>"
        onclick="toggleFav(this, <?= $row['id'] ?>)">
    <i class="<?= isset($favMap[$row['id']]) ? 'fa-solid fa-heart' : 'fa-regular fa-heart' ?>"></i>
</button>

                <img src="uploads/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                <h4><?= htmlspecialchars($row['name']) ?></h4>
                <p class="product-desc"><?= htmlspecialchars($row['description'] ?? '') ?></p>

                <div class="price-box">
                    <?= $row['discount_price'] ? "<span class='old-price'>" . htmlspecialchars($row['price']) . "</span>" : "" ?>
                    <span class="new-price"><?= htmlspecialchars($price) ?> ر.ي</span>
                </div>

                <?php if ($realQty !== null): ?>
                    <div class="qty-label">
                        <!-- لاحظ الـ ID هنا يبدأ بـ qty_idx -->
                        متبقي: <span id="qty_idx_<?= $row['id'] ?>"><?= htmlspecialchars($realQty) ?></span>
                    </div>
                <?php else: ?>
                     <div class="spacer-20"></div>
                <?php endif; ?>

                <!-- زر الإضافة (يستخدم qty_idx ليتطابق مع الـ ID في الأعلى) -->
                <button class="btn-add" onclick="checkSizeAndAdd(<?= $row['id'] ?>, <?= $realQty !== null ? 'true' : 'false' ?>, 'qty_idx_<?= $row['id'] ?>', '<?= htmlspecialchars($row['sizes'] ?? '') ?>')">أضف للسلة</button>
            </div>
        <?php endwhile;

        $latestHTML = ob_get_clean();

        if ($hasLatest):
        ?>
            <div class="section-header">
                <h3>أحدث المنتجات</h3>
                <button onclick="window.location.href='latest.php'">عرض الكل</button>
            </div>
            <div class="products-row">
                <?= $latestHTML ?>
            </div>
        <?php endif; ?>


        <!-- ================= باقي الأقسام ================= -->
        <?php
        // استخدام GROUP BY name لمنع التكرار
        $cats = $pdo->query("SELECT * FROM categories GROUP BY name ORDER BY id DESC");

        while($cat = $cats->fetch(PDO::FETCH_ASSOC)):

            ob_start();
            $hasProducts = false;

            $catSql = "SELECT p.*,
                       (SELECT COALESCE(SUM(qty), 0) FROM cart WHERE product_id = p.id) as total_reserved
                       FROM products p
                       WHERE category_id = ? AND (quantity > 0 OR quantity IS NULL)
                       ORDER BY id DESC";
            $cStmt = $pdo->prepare($catSql);
            // ملاحظة: إذا كان هناك تكرار في الأسماء بـ IDs مختلفة، هذا الاستعلام سيجلب منتجات الـ ID الأول فقط
            // هذا الحل يخفي التكرار في العرض
            $cStmt->execute([$cat['id']]);

            while($prod = $cStmt->fetch(PDO::FETCH_ASSOC)):
                if ($prod['quantity'] !== null) {
                    $realQty = $prod['quantity'] - $prod['total_reserved'];
                    if ($realQty <= 0) continue;
                } else {
                    $realQty = null;
                }
                $hasProducts = true;
                $price = $prod['discount_price'] ? $prod['discount_price'] : $prod['price'];
            ?>
                <div class="product-card">
                    <button class="fav-btn <?= isset($favMap[$prod['id']]) ? 'active' : '' ?>"
                            onclick="toggleFav(this, <?= $prod['id'] ?>)">
                        <i class="<?= isset($favMap[$prod['id']]) ? 'fa-solid fa-heart' : 'fa-regular fa-heart' ?>"></i>
                    </button>

                    <img src="uploads/<?= htmlspecialchars($prod['image']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>">
                    <h4><?= htmlspecialchars($prod['name']) ?></h4>
                    <p class="product-desc"><?= htmlspecialchars($prod['description'] ?? '') ?></p>

                    <div class="price-box">
                        <?= $prod['discount_price'] ? "<span class='old-price'>" . htmlspecialchars($prod['price']) . "</span>" : "" ?>
                        <span class="new-price"><?= htmlspecialchars($price) ?> ر.ي</span>
                    </div>

                    <?php if ($realQty !== null): ?>
                        <div class="qty-label">
                            متبقي: <span id="qty_cat_<?= $prod['id'] ?>"><?= htmlspecialchars($realQty) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="spacer-20"></div>
                    <?php endif; ?>

                    <button class="btn-add" onclick="checkSizeAndAdd(<?= $prod['id'] ?>, <?= $realQty !== null ? 'true' : 'false' ?>, 'qty_cat_<?= $prod['id'] ?>', '<?= htmlspecialchars($prod['sizes'] ?? '') ?>')">أضف للسلة</button>
                </div>
            <?php endwhile;

            $catHTML = ob_get_clean();

            if ($hasProducts):
            ?>
                <div class="section-header">
                    <h3><?= htmlspecialchars($cat['name']) ?></h3>
                    <button onclick="window.location.href='category.php?id=<?= $cat['id'] ?>'">عرض الكل</button>
                </div>
                <div class="products-row">
                    <?= $catHTML ?>
                </div>
            <?php
            endif;
        endwhile;
        ?>

    <!-- زر السلة العائم -->
    <!-- <div class="cart-float" onclick="openCart()">
        🛒 <span class="cart-count" id="cartCount">= getCartCount($pdo, $user_session) ?></span>
    </div> -->
    <!-- زر الأقسام العائم -->
    <!-- <div class="cats-float" onclick="toggleCatMenu()" title="تصفح الأقسام">
        ☰
    </div> -->

    <!-- قائمة الأقسام -->
    <div id="catsMenu" class="cats-menu-container">
        <h4>تصفح الأقسام</h4>

        <!-- رابط ثابت لأحدث المنتجات -->
        <a href="latest.php">✨ أحدث المنتجات</a>

        <?php
        // جلب الأقسام التي تحتوي على منتجات متاحة فقط
        $menuCats = $pdo->query("SELECT * FROM categories");
        while($c = $menuCats->fetch(PDO::FETCH_ASSOC)):

            // التحقق من وجود منتجات غير نافذة في هذا القسم
            $checkSql = "SELECT COUNT(*) FROM products
                         WHERE category_id = ?
                         AND (quantity > 0 OR quantity IS NULL)";

            // ملاحظة: هذا فحص سريع، الفحص الدقيق للمحجوز يتطلب استعلاماً أثقل
            // لكن لغرض القائمة السريعة، هذا يكفي لإخفاء الأقسام الفارغة تماماً
            $stmt = $pdo->prepare($checkSql);
            $stmt->execute([$c['id']]);

            if ($stmt->fetchColumn() > 0):
        ?>
            <a href="category.php?id=<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></a>
        <?php
            endif;
        endwhile;
        ?>
    </div>
    <!-- نافذة السلة -->
    <div id="cartModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('cartModal')">&times;</span>
            <h2 class="modal-title">سلة المشتريات</h2>
            <div id="cartItems"></div>
            <hr>
            <div class="modal-footer">
                <input type="text" id="custName" class="form-input" placeholder="اسم المستلم (الأول والثاني) *" required>
                <input type="number" id="custPhone" class="form-input" placeholder="رقم الهاتف (اختياري)">
                <div class="form-group-relative">
                    <input type="text" id="custAddress" class="form-input input-with-icon"
                           placeholder="العنوان بالتفصيل (أو اضغط الأيقونة 🎯)"
                           required>
                    <button type="button" onclick="getLocation()" title="تحديد موقعي الحالي" class="location-btn">
                        <i class="fa-solid fa-location-crosshairs"></i>
                    </button>
                </div>
                <small class="form-hint">
                    اضغط الأيقونة لتعبئة الحقل برابط الخريطة تلقائياً 🌍
                </small>
                <textarea id="custNotes" class="form-input notes-textarea" placeholder="ملاحظات إضافية (اختياري)"></textarea>
            </div>
            <button id="btnCheckout" class="btn-add btn-checkout" onclick="checkout()">شراء وإصدار فاتورة</button>
        </div>
    </div>

    <!-- نافذة معاينة المنتج -->
    <div id="previewModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('previewModal')">&times;</span>
            <div id="previewContent"></div>
        </div>
    </div>

    <!-- منطقة الفاتورة المخفية -->
    <div id="invoice-area">
        <div class="invoice-header">
        <a href="index.php" class="logo-container">
            <span class="logo-text-store">STORE</span>
            <span class="logo-text-sam">SAM</span>
        </a>
            <p class="invoice-subtitle">فاتورة ضريبية مبسطة</p>
            <p class="invoice-id">رقم الفاتورة: #<span id="invId"></span></p>
        </div>

        <div class="invoice-details">
            <p class="invoice-detail-item"><strong>العميل:</strong> <span id="invName"></span></p>
            <p class="invoice-detail-item"><strong>الهاتف:</strong> <span id="invPhone"></span></p>
            <p class="invoice-detail-item"><strong>العنوان:</strong> <span id="invAddress"></span></p>
            <p class="invoice-detail-item"><strong>التاريخ:</strong> <?= date('Y-m-d h:i A') ?></p>
        </div>

        <table border="1" class="invoice-table">
            <thead class="invoice-table-header">
                <tr>
                    <th class="invoice-table-header-cell">المنتج</th>
                    <th>السعر</th>
                    <th>الكمية</th>
                </tr>
            </thead>
            <tbody id="invBody"></tbody>
        </table>

        <div class="invoice-total-section">
            <h3>الإجمالي: <span id="invTotal" class="invoice-total-amount"></span> ر.ي</h3>
        </div>

        <p>- <span class="invoice-notes">#ملاحظة :</span> يرجى ارسال الفاتورة الى مالك المتجر ليتم تجهيز طلبك، شكرا لتعاملكم معنا.</p>

    </div>

<!-- زر واتساب العائم -->
<!-- <a href="https://wa.me/967738183179" class="whatsapp-float" target="_blank">
    <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" width="30" height="30">
</a> -->
<!-- حساب عدد المفضلات -->
    <?php
    $favCount = 0;
    if(isset($user_session)) {
        $fcStmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE session_id = ?");
        $fcStmt->execute([$user_session]);
        $favCount = $fcStmt->fetchColumn();
    }
    ?>

    <!-- زر المفضلة العائم -->
    <!-- <a href="favorites.php" class="fav-float" title="مفضلاتي">
        <i class="fa-solid fa-heart"></i>
        php if($favCount > 0): ?>
            <span class="fav-count">= $favCount ?></span>
        php endif; ?>
    </a> -->

<!-- نافذة اختيار المقاس -->
<div id="sizeModal" class="modal">
    <div class="modal-content size-modal-content">
        <span class="close-modal" onclick="closeModal('sizeModal')">&times;</span>
        <h3>اختر المقاس المطلوب</h3>
        <div id="sizesContainer" class="sizes-container">
            <!-- سيتم توليد الأزرار هنا بالجافاسكريبت -->
        </div>
    </div>
</div>
<!-- الشريط السفلي الثابت (Bottom Navigation Bar) -->
<nav class="bottom-nav">
        <!-- 5. زر واتساب -->
            <a href="https://wa.me/<?= $sitePhone ?>"  class="nav-item" target="_blank" >
            <i class="fa-brands fa-whatsapp" style="font-size: 1.4rem;"></i>
            <span>تواصل</span>
        </a>
        <!-- 2. زر تتبع الطلب -->
        <a href="track.php" class="nav-item">
            <i class="fa-solid fa-truck-fast"></i>
            <span>تتبع الطلب</span>
        </a>

        <!-- 3. زر السلة (المركزي البارز) -->
        <div class="nav-item center-fab-container">
            <div class="center-fab" onclick="openCart()">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="nav-cart-count" id="cartCount"><?= getCartCount($pdo, $user_session) ?></span>
            </div>
        </div>

        <!-- 4. زر المفضلة -->
        <a href="favorites.php" class="nav-item">
            <i class="fa-regular fa-heart"></i>
            <span>المفضلة</span>
        </a>

        <!-- 1. زر القائمة (الأقسام) -->
        <div class="nav-item" onclick="toggleCatMenu()">
            <i class="fa-solid fa-bars"></i>
            <span>الأقسام</span>
        </div>
    </nav>
      <!-- شريط تثبيت التطبيق (PWA Install Banner) -->
    <div id="pwa-install-banner" style="display:none; position:fixed; top:0; left:0; width:100%; background:#1a2a3a; color:#fff; z-index:10000; padding:10px; box-shadow:0 2px 10px rgba(0,0,0,0.2); align-items:center; justify-content:space-between; direction:rtl;">
        <div style="display:flex; align-items:center; gap:10px;">
            <img src="icons/icon-512x512.png" style="width:40px; height:40px; border-radius:8px; background:#fff; padding:2px; margin: 0 15px 0 0;">
            <div>
                <strong style="font-size:0.9rem; display:block;">تطبيق متجر سام</strong>
                <small style="font-size:0.7rem; color:#c8a76a;">تصفح أسرع وأسهل!</small>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button id="pwa-install-btn" style="background:#c8a76a; color:#1a2a3a; border:none; padding:5px 15px; border-radius:20px; font-weight:bold; cursor:pointer;">تثبيت</button>
            <button onclick="document.getElementById('pwa-install-banner').style.display='none'" style="background:transparent; border:none; color:#fff; font-size:1.2rem; cursor:pointer;">&times;</button>
        </div>
    </div>
<script>
    // 🔴 تأكد أنك وضعت المفتاح هنا، وإلا سيتوقف الكود
    const publicKey = 'BAvM16qSKZq8JWSLwK5EFBixHA-d4uvzePvNzldMCNGf4OR3iXQ-kvWhNWqGlpTrNptDQ2PvSM0wigI7h8dfatc'; 
  function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) { outputArray[i] = rawData.charCodeAt(i); }
        return outputArray;
    }

    // --- المنطق الجديد للظهور التلقائي ---
    document.addEventListener("DOMContentLoaded", function() {
        // التحقق: هل المتصفح يدعم؟ وهل الحالة "افتراضية" (لم يتم الرد سابقاً)؟
        if ('serviceWorker' in navigator && Notification.permission === 'default') {
            
            // التحقق من الذاكرة المحلية: هل ضغط المستخدم "ليس الآن" من قبل؟
            const hasSeenPrompt = localStorage.getItem('push_prompt_seen');
            
            if (!hasSeenPrompt) {
                // إظهار النافذة بعد 3 ثواني لإعطاء الزبون فرصة لرؤية الموقع أولاً
                setTimeout(() => {
                    document.getElementById('push-permission-modal').style.display = 'flex';
                }, 3000);
            }
        }
    });

    // عند الضغط على "ليس الآن"
    function dismissPush() {
        document.getElementById('push-permission-modal').style.display = 'none';
        // حفظ الرفض في المتصفح لكي لا تزعجه النافذة مرة أخرى
        localStorage.setItem('push_prompt_seen', 'true');
    }

    // عند الضغط على "نعم، فعلها"
    async function acceptPush() {
        document.getElementById('push-permission-modal').style.display = 'none';
        
        try {
            const register = await navigator.serviceWorker.register('sw.js');
            const registration = await navigator.serviceWorker.ready;

            // هنا سيظهر طلب المتصفح الرسمي
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(publicKey)
            });

            // إرسال البيانات
            const p256dh = btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh'))));
            const auth = btoa(String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth'))));

            let fd = new FormData();
            fd.append('action', 'save_subscription');
            fd.append('endpoint', subscription.endpoint);
            fd.append('p256dh', p256dh);
            fd.append('auth', auth);
            
            await fetch('api.php', { method: 'POST', body: fd });
            
            alert("تم التفعيل بنجاح! شكراً لك.");
            
        } catch (error) {
            console.error("لم يتم التفعيل:", error);
            // إذا رفض الإذن الرسمي، لا تظهر النافذة مرة أخرى
            localStorage.setItem('push_prompt_seen', 'true');
        }
    }
</script>
      
<!-- نافذة طلب الإذن بالإشعارات -->
<div id="push-permission-modal" class="modal" style="display:none; z-index: 10001;">
    <div class="modal-content" style="text-align: center; max-width: 350px; padding: 30px;">
        <div style="background: #f0f8ff; width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
            <i class="fa-solid fa-bell" style="font-size: 2rem; color: #007bff;"></i>
        </div>
        
        <h3 style="margin-bottom: 10px; color: #1a2a3a;">تفعيل التنبيهات؟</h3>
        <p style="color: #666; font-size: 0.9rem; margin-bottom: 20px;">
            هل تود أن نرسل لك إشعاراً فور وصول منتجات جديدة أو عروض حصرية؟
        </p>
        
        <div style="display: flex; gap: 10px;">
            <button onclick="acceptPush()" class="btn-add" style="background: #1a2a3a; flex: 1;">نعم، فعلها</button>
            <button onclick="dismissPush()" class="btn-add" style="background: #eee; color: #333; flex: 1;">ليس الآن</button>
        </div>
    </div>
</div>
    <script src="script.js"></script>
      
</body>
</html>