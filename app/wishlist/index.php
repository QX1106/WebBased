<?php
require '../_base.php';

auth('Member');

$member_id = $_user->member_id;


// Get wishlist products
$stm = $pdo->prepare("
    SELECT
        w.wishlist_id,
        w.created_at,
        p.id AS product_id,
        p.name,
        p.price,
        p.stock_qty,
        p.photo

    FROM wishlist w

    JOIN product p
        ON w.product_id = p.id

    WHERE w.member_id = ?

    ORDER BY w.created_at DESC
");

$stm->execute([$member_id]);

$wishlist_items = $stm->fetchAll();


$_title = 'My Wishlist';

require '../_head.php';
?>


<h1>My Wishlist</h1>


<?php if ($wishlist_items): ?>

    <div class="product-grid" id="wishlist-grid">

        <?php foreach ($wishlist_items as $item): ?>

            <div
                class="product-card wishlist-card"
                data-product-id="<?= $item->product_id ?>"
            >

                <div class="wishlist-card-top">

                    <button
                        type="button"
                        class="wishlist-toggle wishlist-active"
                        data-product-id="<?= $item->product_id ?>"
                        title="Remove from Wishlist"
                    >
                        ♥
                    </button>

                </div>


                <a href="/product/view.php?id=<?= $item->product_id ?>">

                    <div class="product-image">

                        <?php if ($item->photo): ?>

                            <img
                                src="/photos/<?= h($item->photo) ?>"
                                alt="<?= h($item->name) ?>"
                            >

                        <?php else: ?>

                            <div class="no-photo">
                                No Photo
                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="product-info">

                        <h3>
                            <?= h($item->name) ?>
                        </h3>


                        <div class="product-price">

                            RM <?= number_format(
                                $item->price,
                                2
                            ) ?>

                        </div>


                        <?php if ($item->stock_qty <= 0): ?>

                            <div class="product-stock">
                                Out of Stock
                            </div>

                        <?php else: ?>

                            <div class="product-stock">
                                <?= $item->stock_qty ?> available
                            </div>

                        <?php endif; ?>

                    </div>

                </a>

            </div>

        <?php endforeach; ?>

    </div>


<?php else: ?>

    <div class="empty-orders" id="wishlist-empty">

        <h2>Your wishlist is empty</h2>

        <p>
            Save products you like and come back to them later.
        </p>

        <a href="/" class="btn-accent">
            Continue Shopping
        </a>

    </div>

<?php endif; ?>


<script>
(function () {

    function postJSON(url, data) {

        return fetch(url, {
            method: 'POST',

            headers: {
                'Content-Type':
                    'application/x-www-form-urlencoded'
            },

            body: Object.keys(data)
                .map(function (key) {

                    return encodeURIComponent(key)
                        + '='
                        + encodeURIComponent(data[key]);

                })
                .join('&')
        })
        .then(function (response) {
            return response.json();
        });
    }


    document
        .querySelectorAll('.wishlist-toggle')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function (e) {

                    e.preventDefault();

                    var productId =
                        button.dataset.productId;


                    postJSON(
                        '/wishlist/toggle.php',
                        {
                            product_id: productId
                        }
                    )
                    .then(function (data) {

                        if (!data.success) {

                            alert(
                                data.message ||
                                'Something went wrong.'
                            );

                            return;
                        }


                        // On wishlist page,
                        // removing means remove the card
                        if (!data.wishlisted) {

                            var card =
                                button.closest(
                                    '.wishlist-card'
                                );

                            if (card) {
                                card.remove();
                            }


                            // Check whether wishlist
                            // is now empty
                            var remaining =
                                document.querySelectorAll(
                                    '.wishlist-card'
                                );

                            if (remaining.length === 0) {
                                location.reload();
                            }
                        }

                    })
                    .catch(function () {

                        alert(
                            'Something went wrong. Please try again.'
                        );

                    });

                }
            );

        });

})();
</script>


<?php require '../_foot.php'; ?>