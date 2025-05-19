<?php
// includes/cart-functions.php

/**
 * Lisää tuote ostoskoriin
 */
function addToCart($user_id, $product_id, $quantity = 1) {
    global $pdo;
    
    try {
        // Tarkistetaan onko tuotetta varastossa
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product || $product['stock'] < $quantity) {
            return [
                'success' => false,
                'message' => 'Tuotetta ei ole riittävästi varastossa.'
            ];
        }
        
        // Tarkistetaan onko tuote jo ostoskorissa
        $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $cart_item = $stmt->fetch();
        
        if ($cart_item) {
            // Päivitetään määrää
            $new_quantity = $cart_item['quantity'] + $quantity;
            
            // Tarkistetaan että uusi määrä ei ylitä varastoa
            if ($new_quantity > $product['stock']) {
                return [
                    'success' => false,
                    'message' => 'Tuotetta ei ole riittävästi varastossa.'
                ];
            }
            
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->execute([$new_quantity, $cart_item['id']]);
        } else {
            // Lisätään uusi tuote ostoskoriin
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity]);
        }
        
        return [
            'success' => true,
            'message' => 'Tuote lisätty ostoskoriin.'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Virhe lisättäessä tuotetta ostoskoriin: ' . $e->getMessage()
        ];
    }
}

/**
 * Päivitä tuotteen määrä ostoskorissa
 */
function updateCartQuantity($user_id, $product_id, $quantity) {
    global $pdo;
    
    try {
        // Tarkistetaan onko tuotetta varastossa
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product || $product['stock'] < $quantity) {
            return [
                'success' => false,
                'message' => 'Tuotetta ei ole riittävästi varastossa.'
            ];
        }
        
        if ($quantity <= 0) {
            // Poistetaan tuote ostoskorista
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            
            return [
                'success' => true,
                'message' => 'Tuote poistettu ostoskorista.'
            ];
        } else {
            // Päivitetään määrä
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $user_id, $product_id]);
            
            return [
                'success' => true,
                'message' => 'Ostoskorin määrä päivitetty.'
            ];
        }
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Virhe päivitettäessä ostoskoria: ' . $e->getMessage()
        ];
    }
}

/**
 * Poista tuote ostoskorista
 */
function removeFromCart($user_id, $product_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        
        return [
            'success' => true,
            'message' => 'Tuote poistettu ostoskorista.'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Virhe poistettaessa tuotetta ostoskorista: ' . $e->getMessage()
        ];
    }
}

/**
 * Tyhjennä ostoskori
 */
function clearCart($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        return [
            'success' => true,
            'message' => 'Ostoskori tyhjennetty.'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Virhe tyhjennettäessä ostoskoria: ' . $e->getMessage()
        ];
    }
}

/**
 * Hae käyttäjän ostoskori
 */
function getCart($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        
        $cart_items = $stmt->fetchAll();
        $total = 0;
        
        foreach ($cart_items as &$item) {
            $item['subtotal'] = $item['price'] * $item['quantity'];
            $total += $item['subtotal'];
        }
        
        return [
            'success' => true,
            'items' => $cart_items,
            'total' => $total,
            'count' => count($cart_items)
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Virhe haettaessa ostoskoria: ' . $e->getMessage(),
            'items' => [],
            'total' => 0,
            'count' => 0
        ];
    }
}

/**
 * Luo tilaus ostoskorin perusteella
 */
function createOrder($user_id) {
    global $pdo;
    
    try {
        // Haetaan ostoskori
        $cart = getCart($user_id);
        
        if (!$cart['success'] || count($cart['items']) === 0) {
            return [
                'success' => false,
                'message' => 'Ostoskori on tyhjä.'
            ];
        }
        
        // Aloitetaan transaktio
        $pdo->beginTransaction();
        
        // Luodaan tilaus
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price) VALUES (?, ?)");
        $stmt->execute([$user_id, $cart['total']]);
        $order_id = $pdo->lastInsertId();
        
        // Lisätään tilauksen tuotteet
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        
        foreach ($cart['items'] as $item) {
            // Tarkistetaan vielä kerran varastotilanne
            $stock_stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stock_stmt->execute([$item['product_id']]);
            $product = $stock_stmt->fetch();
            
            if (!$product || $product['stock'] < $item['quantity']) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Tuotetta ' . $item['name'] . ' ei ole riittävästi varastossa.'
                ];
            }
            
            // Lisätään ti