<?php
require '../_base.php';

auth('Member');

$member_id = $_user->member_id;

$id = get('id');


// ----------------------------------------------------------------------
// Get Product
// ----------------------------------------------------------------------

$stm = $pdo->prepare("
    SELECT
        p.*,
        c.name AS category_name

    FROM product p

    JOIN category c
        ON c.id = p.category_id

    WHERE p.id = ?
");

$stm->execute([$id]);

$product = $stm->fetch();


if (!$product) {

    temp('info', 'Product not found.');

    redirect('/');
}



// ----------------------------------------------------------------------
// Check Wishlist
// ----------------------------------------------------------------------

$stm = $pdo->prepare("
    SELECT COUNT(*)
    FROM wishlist
    WHERE member_id = ?
      AND product_id = ?
");

$stm->execute([
    $member_id,
    $product->id
]);

$is_wishlisted =
    $stm->fetchColumn() > 0;



// ----------------------------------------------------------------------
// YouTube Embed
// ----------------------------------------------------------------------

function youtube_embed_url($url)
{
    if (
        $url &&
        preg_match(
            '/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([a-zA-Z0-9_-]{11})/',
            $url,
            $m
        )
    ) {
        return
            'https://www.youtube-nocookie.com/embed/'
            . $m[1];
    }

    return null;
}


$embed_url =
    youtube_embed_url(
        $product->video_url
    );



// ----------------------------------------------------------------------
// Gather Product Photos
// ----------------------------------------------------------------------

$gallery_stm = $pdo->prepare("
    SELECT photo
    FROM product_photo
    WHERE product_id = ?
    ORDER BY sort_order
");

$gallery_stm->execute([$id]);


$all_photos = [];


if ($product->photo) {
    $all_photos[] =
        $product->photo;
}


foreach (
    $gallery_stm->fetchAll()
    as $gp
) {

    $all_photos[] =
        $gp->photo;
}



// ----------------------------------------------------------------------
// Build Slider
// ----------------------------------------------------------------------

$slides = [];


if ($embed_url) {

    $slides[] = [
        'type' => 'video',
        'src'  => $embed_url
    ];
}


foreach ($all_photos as $ph) {

    $slides[] = [
        'type' => 'image',
        'src'  => "/photos/$ph"
    ];
}



$_title = 'Product Detail';

require '../_head.php';
?>


<p>
    <a href="/">
        &larr; Back to Store
    </a>
</p>


<h1>Product Detail</h1>


<div
    class="pd-wrap"
    style="
        display:flex;
        gap:32px;
        align-items:flex-start;
        flex-wrap:wrap;
    "
>


    <!-- ============================================================= -->
    <!-- LEFT: MEDIA                                                   -->
    <!-- ============================================================= -->

    <div
        class="pd-media"
        style="flex:0 0 400px;"
    >


        <?php if ($slides): ?>

            <div id="slider">


                <div
                    id="slides"
                    style="border:1px solid #ddd;"
                >

                    <?php foreach (
                        $slides
                        as $i => $slide
                    ): ?>

                        <div
                            class="slide"
                            data-index="<?= $i ?>"
                            style="<?=
                                $i === 0
                                    ? ''
                                    : 'display:none;'
                            ?>"
                        >


                            <?php if (
                                $slide['type']
                                === 'video'
                            ): ?>

                                <iframe
                                    width="400"
                                    height="300"
                                    src="<?= h(
                                        $slide['src']
                                    ) ?>"
                                    frameborder="0"
                                    allowfullscreen
                                ></iframe>


                            <?php else: ?>

                                <img
                                    src="<?= h(
                                        $slide['src']
                                    ) ?>"
                                    width="400"
                                    height="300"
                                >

                            <?php endif; ?>


                        </div>

                    <?php endforeach; ?>

                </div>



                <?php if (
                    count($slides) > 1
                ): ?>


                    <div
                        style="
                            display:flex;
                            gap:4px;
                            margin-top:6px;
                            flex-wrap:wrap;
                        "
                    >


                        <?php foreach (
                            $slides
                            as $i => $slide
                        ): ?>


                            <?php if (
                                $slide['type']
                                === 'video'
                            ): ?>

                                <div
                                    class="slider-thumb"
                                    data-index="<?= $i ?>"
                                    style="
                                        cursor:pointer;
                                        width:50px;
                                        height:38px;
                                        background:#000;
                                        color:#fff;
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
                                        font-size:16px;
                                    "
                                >
                                    &#9654;
                                </div>


                            <?php else: ?>

                                <img
                                    src="<?= h(
                                        $slide['src']
                                    ) ?>"
                                    width="50"
                                    height="38"
                                    class="slider-thumb"
                                    data-index="<?= $i ?>"
                                    style="
                                        cursor:pointer;
                                        border:1px solid #ccc;
                                    "
                                >

                            <?php endif; ?>


                        <?php endforeach; ?>


                    </div>



                    <div
                        style="
                            display:flex;
                            justify-content:space-between;
                            align-items:center;
                            margin-top:6px;
                        "
                    >

                        <button
                            type="button"
                            id="slider-prev"
                        >
                            &larr; Prev
                        </button>


                        <span id="slider-count">

                            1 /
                            <?= count($slides) ?>

                        </span>


                        <button
                            type="button"
                            id="slider-next"
                        >
                            Next &rarr;
                        </button>

                    </div>


                <?php endif; ?>


            </div>


        <?php else: ?>

            <div class="no-photo">
                No Photo
            </div>

        <?php endif; ?>


    </div>



    <!-- ============================================================= -->
    <!-- RIGHT: PRODUCT DETAILS                                        -->
    <!-- ============================================================= -->

    <div
        class="pd-info"
        style="
            flex:1 1 320px;
            min-width:280px;
        "
    >


        <!-- Product Name + Wishlist -->

        <div
            style="
                display:flex;
                justify-content:space-between;
                align-items:flex-start;
                gap:20px;
            "
        >

            <h2
                style="
                    margin:0 0 4px;
                    font-size:50px;
                "
            >
                <?= h($product->name) ?>
            </h2>


            <button
                type="button"
                id="wishlist-toggle"
                data-product-id="<?= $product->id ?>"
                class="<?= $is_wishlisted
                    ? 'wishlisted'
                    : ''
                ?>"
                title="<?= $is_wishlisted
                    ? 'Remove from Wishlist'
                    : 'Add to Wishlist'
                ?>"
                style="
                    background:none;
                    border:none;
                    padding:5px;
                    margin:0;
                    font-size:32px;
                    cursor:pointer;
                    color:#d0021b;
                "
            >
                <?= $is_wishlisted
                    ? '♥'
                    : '♡'
                ?>
            </button>

        </div>



        <!-- Category -->

        <div
            style="
                color:#888;
                font-size:20px;
                margin-bottom:14px;
            "
        >
            <?= h(
                $product->category_name
            ) ?>
        </div>



        <!-- Price -->

        <div
            style="
                font-size:30px;
                font-weight:bold;
                color:#d0021b;
                margin-bottom:14px;
            "
        >

            RM <?= number_format(
                $product->price,
                2
            ) ?>

        </div>



        <!-- Product Information -->

        <table
            class="form-table"
            style="
                width:100%;
                max-width:450px;
            "
        >

            <tr>

                <td
                    style="
                        width:130px;
                        font-weight:bold;
                        vertical-align:top;
                        padding:6px 0;
                    "
                >
                    Available
                </td>

                <td style="padding:6px 0;">

                    <?= $product->stock_qty ?>

                </td>

            </tr>


            <tr>

                <td
                    style="
                        width:130px;
                        font-weight:bold;
                        vertical-align:top;
                        padding:6px 12px 6px 0;
                    "
                >
                    Description
                </td>

                <td style="padding:6px 0;">

                    <?= nl2br(
                        h($product->description)
                    ) ?>

                </td>

            </tr>

        </table>



        <!-- ========================================================= -->
        <!-- Quantity                                                  -->
        <!-- ========================================================= -->

        <div style="margin-top:20px;">

            <div
                style="
                    font-weight:bold;
                    margin-bottom:8px;
                "
            >
                Quantity
            </div>


            <div
                style="
                    display:grid;
                    grid-template-columns:
                        36px 50px 36px;
                    border:1px solid #999;
                    width:122px;
                    border-radius:4px;
                    overflow:hidden;
                "
            >


                <button
                    type="button"
                    id="qty-minus"
                    style="
                        height:36px;
                        border:none;
                        background:#f7f4ee;
                        color:#333;
                        cursor:pointer;
                        font-size:18px;
                        line-height:1;
                        padding:0;
                        margin:0;
                    "
                >
                    &minus;
                </button>



                <input
                    type="text"
                    id="qty-input"
                    value="1"
                    readonly
                    style="
                        height:36px;
                        padding:0;
                        margin:0;
                        border:none;
                        border-left:
                            1px solid #999;
                        border-right:
                            1px solid #999;
                        text-align:center;
                        font-size:14px;
                        background:#f7f4ee;
                        color:#333;
                    "
                >



                <button
                    type="button"
                    id="qty-plus"
                    style="
                        height:36px;
                        border:none;
                        background:#f7f4ee;
                        color:#333;
                        cursor:pointer;
                        font-size:18px;
                        line-height:1;
                        padding:0;
                        margin:0;
                    "
                >
                    &plus;
                </button>


            </div>

        </div>



        <!-- ========================================================= -->
        <!-- Cart / Buy Buttons                                        -->
        <!-- ========================================================= -->

        <div
            style="
                display:flex;
                gap:12px;
                margin-top:20px;
                max-width:450px;
            "
        >


            <button
                type="button"
                id="add-to-cart"
                style="
                    flex:1;
                    padding:12px;
                    border:1px solid #d0021b;
                    background:#fff;
                    color:#d0021b;
                    font-weight:bold;
                    font-size:14px;
                    cursor:pointer;
                "
            >
                Add to Cart
            </button>


            <button
                type="button"
                id="buy-now"
                style="
                    flex:1;
                    padding:12px;
                    border:1px solid #d0021b;
                    background:#d0021b;
                    color:#fff;
                    font-weight:bold;
                    font-size:14px;
                    cursor:pointer;
                "
            >
                Buy Now
            </button>

        </div>



        <!-- Hidden Cart Form -->

        <form
            method="post"
            action="/cart/add.php"
            id="add-to-cart-form"
            style="display:none;"
        >

            <input
                type="hidden"
                name="product_id"
                value="<?= (int)$product->id ?>"
            >

            <input
                type="hidden"
                name="qty"
                id="add-to-cart-qty"
                value="1"
            >

        </form>


    </div>

</div>



<!-- ================================================================ -->
<!-- Product Slider JavaScript                                        -->
<!-- ================================================================ -->

<?php if (count($slides) > 1): ?>

<script>

(function () {

    var idx = 0;

    var slideCount =
        <?= count($slides) ?>;

    var slideEls =
        document.querySelectorAll(
            '.slide'
        );

    var count =
        document.getElementById(
            'slider-count'
        );


    function show(i) {

        idx =
            (i + slideCount)
            % slideCount;

        slideEls.forEach(
            function (el) {

                el.style.display =
                    (
                        parseInt(
                            el.dataset.index,
                            10
                        ) === idx
                    )
                    ? ''
                    : 'none';

            }
        );

        count.textContent =
            (idx + 1)
            + ' / '
            + slideCount;
    }


    document
        .getElementById('slider-prev')
        .addEventListener(
            'click',
            function () {
                show(idx - 1);
            }
        );


    document
        .getElementById('slider-next')
        .addEventListener(
            'click',
            function () {
                show(idx + 1);
            }
        );


    document
        .querySelectorAll(
            '.slider-thumb'
        )
        .forEach(function (thumb) {

            thumb.addEventListener(
                'click',
                function () {

                    show(
                        parseInt(
                            this.dataset.index,
                            10
                        )
                    );

                }
            );

        });

})();

</script>

<?php endif; ?>



<!-- ================================================================ -->
<!-- Quantity + Cart + Wishlist JavaScript                            -->
<!-- ================================================================ -->

<script>

(function () {


    // --------------------------------------------------------------
    // Quantity
    // --------------------------------------------------------------

    var qtyInput =
        document.getElementById(
            'qty-input'
        );

    var max =
        <?= (int)$product->stock_qty ?>;


    document
        .getElementById('qty-minus')
        .addEventListener(
            'click',
            function () {

                var val =
                    parseInt(
                        qtyInput.value,
                        10
                    ) || 1;

                if (val > 1) {
                    qtyInput.value =
                        val - 1;
                }

            }
        );


    document
        .getElementById('qty-plus')
        .addEventListener(
            'click',
            function () {

                var val =
                    parseInt(
                        qtyInput.value,
                        10
                    ) || 1;

                if (val < max) {
                    qtyInput.value =
                        val + 1;
                }

            }
        );



    // --------------------------------------------------------------
    // Add to Cart
    // --------------------------------------------------------------

    document
        .getElementById('add-to-cart')
        .addEventListener(
            'click',
            function () {

                document
                    .getElementById(
                        'add-to-cart-qty'
                    )
                    .value =
                        qtyInput.value;

                document
                    .getElementById(
                        'add-to-cart-form'
                    )
                    .submit();

            }
        );



    // --------------------------------------------------------------
    // Buy Now
    // --------------------------------------------------------------

    document
        .getElementById('buy-now')
        .addEventListener(
            'click',
            function () {

                var qty =
                    parseInt(
                        qtyInput.value,
                        10
                    ) || 1;

                alert(
                    'Proceeding to buy '
                    + qty
                    + ' item(s).'
                );

            }
        );



    // --------------------------------------------------------------
    // Wishlist
    // --------------------------------------------------------------

    var wishlistButton =
        document.getElementById(
            'wishlist-toggle'
        );


    if (wishlistButton) {

        wishlistButton
            .addEventListener(
                'click',
                function () {

                    var productId =
                        wishlistButton
                            .dataset
                            .productId;


                    fetch(
                        '/wishlist/toggle.php',
                        {
                            method: 'POST',

                            headers: {
                                'Content-Type':
                                    'application/x-www-form-urlencoded'
                            },

                            body:
                                'product_id='
                                + encodeURIComponent(
                                    productId
                                )
                        }
                    )
                    .then(function (response) {

                        return response.json();

                    })
                    .then(function (data) {

                        if (!data.success) {

                            alert(
                                data.message ||
                                'Something went wrong.'
                            );

                            return;
                        }


                        if (data.wishlisted) {

                            wishlistButton
                                .textContent = '♥';

                            wishlistButton
                                .title =
                                'Remove from Wishlist';

                            wishlistButton
                                .classList
                                .add(
                                    'wishlisted'
                                );

                        }
                        else {

                            wishlistButton
                                .textContent = '♡';

                            wishlistButton
                                .title =
                                'Add to Wishlist';

                            wishlistButton
                                .classList
                                .remove(
                                    'wishlisted'
                                );

                        }

                    })
                    .catch(function () {

                        alert(
                            'Something went wrong. Please try again.'
                        );

                    });

                }
            );
    }


})();

</script>


<?php require '../_foot.php'; ?>