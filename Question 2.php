<?php
session_start();

// Initialize session values if not set
if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 200000;
    $_SESSION['history'] = [];
    $_SESSION['transactions'] = [];
    $_SESSION['transaction_id'] = 1;
}

// Handle form submission BEFORE rendering HTML
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Handle Payment
    if (isset($_POST['PayAmount'])) {
        $Amount = floatval($_POST['PayAmount']);
        $Type = htmlspecialchars($_POST['PayOp']);
        $Recever = htmlspecialchars($_POST['Recever']);

        switch ($Type) {
            case 'Credit Card': 
                $Fee = 0.01 * $Amount; 
                break;
            case 'PayPal': 
                $Fee = 0.03 * $Amount; 
                break;
            case 'Cryptocurrency': 
                $Fee = 0.05 * $Amount; 
                break;
            default: $Fee = 0.00; break;
        }

        if ($Amount > 100000) {
            $message = '<p><b style="color: red;">Fraud detection</b>. Limit set by account holder</p>';
        } else {
            $_SESSION['balance'] -= ($Amount + $Fee);
            $message = '<p><b style="color: green;">Payment successful.</b></p>';
            $id = $_SESSION['transaction_id']++;

            $_SESSION['transactions'][] = [
                'id' => $id,
                'amount' => $Amount,
                'fee' => $Fee,
                'receiver' => $Recever,
                'type' => $Type,
                'refunded' => false
            ];

            $_SESSION['history'][] = "<p style='color:red;'>[ID: {$id}] R{$Amount} paid to {$Recever} via {$Type}. (Fee: R{$Fee})</p>";
        }
    }

    // Handle Deposit
    elseif (isset($_POST['RecivedAmount'])) {
        $Amount = floatval($_POST['RecivedAmount']);
        $Type = htmlspecialchars($_POST['PayOp']);
        $Payer = htmlspecialchars($_POST['Payer']);
        $id = $_SESSION['transaction_id']++;

        $_SESSION['balance'] += $Amount;
        $message = '<p><b style="color: green;">Deposit successful.</b></p>';
        $_SESSION['history'][] = "<p style='color:green;'>[ID: {$id}] R{$Amount} deposited by {$Payer} via {$Type}.</p>";
    }

    // Handle Refund
    elseif (isset($_POST['refundID'])) {
        $refundID = intval($_POST['refundID']);
        $found = false;

        foreach ($_SESSION['transactions'] as &$txn) {
            if ($txn['id'] === $refundID) {
                if ($txn['refunded']) {
                    $message = "<p style='color: orange;'>Transaction ID $refundID already refunded.</p>";
                } else {
                    $_SESSION['balance'] += ($txn['amount'] + $txn['fee']);
                    $txn['refunded'] = true;
                    $message = "<p style='color: green;'>Refund successful for ID $refundID (R{$txn['amount']} + R{$txn['fee']}).</p>";
                    $_SESSION['history'][] = "<p style='color:green;'>[ID: {$txn['id']}] Refund issued for R{$txn['amount']} to {$txn['receiver']} via {$txn['type']}.</p>";
                }
                $found = true;
                break;
            }
        }
        unset($txn);

        if (!$found) {
            $message = "<p style='color: red;'>Transaction ID $refundID not found.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>FNB</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }

        h1 {
            text-align: center;
            color: #003366;
        }

        .container {
            display: flex;
            gap: 20px;
        }

        .left, .right {
            flex: 1;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .form-block {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 10px;
        }

        h2 {
            color: #004080;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #004080;
            color: white;
        }

        .green { background-color: #d4edda; }
        .blue { background-color: #d1ecf1; }
        .red { background-color: #f8d7da; }

        .status-message {
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<h1>First National Bank</h1>
<h2>Welcome Kaelin!</h2>
<h3>Your current balance is <b style="color: green;">R<?= number_format($_SESSION['balance'], 2) ?></b></h3>

<div class="container">
    <!-- LEFT COLUMN -->
    <div class="left">
        <h2>Transactions</h2>

        <div style="display: flex; gap: 20px;">
            <form class="form-block" action="" method="post" style="flex: 1;">
                <h4>Make a Payment</h4>
                <label>Amount (R): </label><br>
                <input type="text" name="PayAmount" required><br><br>
                <fieldset>
                    <legend>Choose payment option:</legend>
                    <input type="radio" name="PayOp" value="Credit Card" required> Credit Card<br>
                    <input type="radio" name="PayOp" value="PayPal"> PayPal<br>
                    <input type="radio" name="PayOp" value="Cryptocurrency"> Cryptocurrency<br>
                </fieldset><br>
                <label>Receiver's name</label><br>
                <input type="text" name="Recever" required><br><br>
                <input type="submit" value="Pay">
            </form>

            <form class="form-block" action="" method="post" style="flex: 1;">
                <h4>Receive a Payment</h4>
                <label>Amount (R): </label><br>
                <input type="text" name="RecivedAmount" required><br><br>
                <fieldset>
                    <legend>Choose payment option:</legend>
                    <input type="radio" name="PayOp" value="Credit Card" required> Credit Card<br>
                    <input type="radio" name="PayOp" value="PayPal"> PayPal<br>
                    <input type="radio" name="PayOp" value="Cryptocurrency"> Cryptocurrency<br>
                </fieldset><br>
                <label>Payer's name</label><br>
                <input type="text" name="Payer" required><br><br>
                <input type="submit" value="Deposit">
            </form>
        </div>

        <form class="form-block" method="post" action="">
            <h4>Request a Refund</h4>
            <label>Enter Transaction ID:</label><br>
            <input type="number" name="refundID" min="1" required><br><br>
            <input type="submit" value="Refund">
        </form>

        <?php if (isset($message)): ?>
            <div class="status-message"><?= $message ?></div>
        <?php endif; ?>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="right">
        <h2>Transaction History</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Description</th>
            </tr>
            <?php
            foreach (array_reverse($_SESSION['history']) as $entry) {
                $class = '';
                if (strpos($entry, 'deposited') !== false) {
                    $class = 'green';
                } elseif (strpos($entry, 'Refund') !== false || strpos($entry, 'refunded') !== false) {
                    $class = 'blue';
                } elseif (strpos($entry, 'paid') !== false) {
                    $class = 'red';
                }
                echo "<tr class='$class'><td colspan='3'>{$entry}</td></tr>";
            }
            ?>
        </table>
    </div>
</div>

</body>
</html>