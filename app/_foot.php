<?php if ($_user && in_array($_user->role, ['Admin', 'Super Admin'])): ?>
        </main>
    </div>
<?php else: ?>
</main>
<?php endif; ?>

<footer>
    <p>&copy; <?= date('Y') ?> Stationary Online Store. All rights reserved.</p>
</footer>

</body>
</html>
