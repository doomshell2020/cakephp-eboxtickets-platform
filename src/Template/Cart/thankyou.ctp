<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Thank You - Booking Confirmed</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f8fb;
            margin: 0;
            padding: 0;
        }

        .thank-you-container {
            max-width: 600px;
            margin: 80px auto;
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-align: center;
        }

        .thank-you-container h1 {
            color: #28a745;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .thank-you-container p {
            color: #555;
            font-size: 1.1rem;
        }

        .booking-id {
            background-color: #e9f7ef;
            color: #2d6a4f;
            padding: 10px;
            margin-top: 20px;
            border-radius: 6px;
            font-weight: bold;
        }

        .btn-home {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .btn-home:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="thank-you-container">
        <h1>Thank You!</h1>
        <p>Your ticket has been successfully booked.</p>

        <?php if (!empty($bookingId)): ?>
            <div class="booking-id">
                Your Booking ID: <?= h($bookingId) ?>
            </div>
        <?php endif; ?>

        <a href="<?php echo SITE_URL; ?>tickets/myticket" class="btn-home">Go to My tickets</a>
    </div>
</body>
</html>
