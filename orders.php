<?php

declare(strict_types=1);

// Загрузка зазказв
function loadOrders(string $filePath): array 
{
    if (!is_file($filePath) || !is_readable($filePath)) {
        throw new RuntimeException("Не получилось прочитать файл: {$filePath}");
    }
    
    $json = file_get_contents($filePath);    
    if ($json === false) {
        throw new RuntimeException("Ошибка чтения файла: {$filePath}");
    }
    
    $orders = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    return $orders;
}

// Валидация заказов
function validateOrders(array $orders): array 
{
    $requiredFields = ['id', 'customer', 'amount', 'status'];
    $statusEnum = ['paid', 'pending'];
    
    $validatedOrders = [];
    
    foreach ($orders as $index => $order) {
        if (!is_array($order)) {
            echo "Заказ {$index}: некорректный формат\n";
            continue;
        }
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $order)) {
                echo "Заказ с индексом {$index}: отсутствует поле {$field}\n";
                continue 2;
            }
        }
        
        if (
            (!is_int($order['amount']) && !is_float($order['amount'])) ||
            $order['amount'] < 0
        ) {
            echo "Заказ {$order['id']}: некорректная сумма\n";
            continue;
        }
        
        if (!in_array($order['status'], $statusEnum, true)) {
            echo "Заказ {$order['id']}: непонятный статус\n";
            continue;
        }
        
        $validatedOrders[] = $order;
    }
    
    return $validatedOrders;
}

function groupOrdersByStatus(array $orders): array
{
    $groups = [];

    
    foreach ($orders as $order) {
        $status = $order['status'];
        
        if (!array_key_exists($status, $groups)) {
            $groups[$status] = 0;
        }
        
        $groups[$status]++;
    }

    return $groups;
}

// Данные оплаченных заказов
function getPaidOrdersInfo(array $paidOrders): array
{
    $countPaidOrders = count($paidOrders); // Количество оплаченных заказов
    
    $amounts = array_column($paidOrders, 'amount');
    $totalAmounts = array_sum($amounts); // Общая сумма оплаченных заказов:
    
    $mostOrder = null;
    
    if (!empty($paidOrders)) {
        $maxAmount = max($amounts);
        $maxKey = array_search($maxAmount, $amounts, true);
        
        $mostOrder = $paidOrders[$maxKey];
    }
    
    return ['count' => $countPaidOrders, 'total' => $totalAmounts, 'most_order' => $mostOrder];
}

try {
    $orders = loadOrders(__DIR__ . '/orders.json');
} catch (JsonException | RuntimeException $e) {
    fwrite(STDERR, "Ошибка: {$e->getMessage()}\n");
    exit(1);
}

$orders = validateOrders($orders);

$paidOrders = array_values(array_filter(
    $orders,
    fn($order) => $order['status'] === "paid"
));

$ordersInfo = getPaidOrdersInfo($paidOrders);
$groups = groupOrdersByStatus($orders);


echo "Количество оплаченных заказов: {$ordersInfo['count']}\n";
echo "Общая сумма оплаченных заказов: {$ordersInfo['total']}\n";

if ($ordersInfo['most_order'] !== null) {
    $mostOrder = $ordersInfo['most_order'];
    echo "Самый дорогой оплаченный заказ: № {$mostOrder['id']} на сумму {$mostOrder['amount']}\n";
} else {
    echo "Оплаченные заказы отсутствуют\n";
}

echo "Количество заказов по статусам:\n";

foreach ($groups as $status => $count) {
    echo "{$status}: {$count}\n";
}