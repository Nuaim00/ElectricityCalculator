<?php
// ==============================
//  Electricity Calculator (PHP)
// ==============================

function toFloat($value) {
  // Allow commas and spaces (e.g., "3,24" -> "3.24")
  $value = str_replace([" ", ","], ["", "."], (string)$value);
  return is_numeric($value) ? (float)$value : null;
}

function calcPowerW($voltage, $current) {
  // Power (W) = V * A
  return $voltage * $current;
}

function calcPowerKW($powerW) {
  // kW = W / 1000
  return $powerW / 1000.0;
}

function calcRateRM($rateSenPerKwh) {
  // RM/kWh = (sen/kWh) / 100
  return $rateSenPerKwh / 100.0;
}

function buildHourlyRows($powerKW, $rateRM, $maxHour = 24) {
  $rows = [];
  for ($h = 1; $h <= $maxHour; $h++) {
    // Cumulative energy up to hour h
    $energyKwh = $powerKW * $h;
    $totalRM = $energyKwh * $rateRM;

    $rows[] = [
      "hour" => $h,
      "energy" => $energyKwh,
      "total" => $totalRM
    ];
  }
  return $rows;
}

// Defaults (so page still shows something nice)
$voltage = 19;
$current = 3.24;
$rateSen = 21.80;

$errors = [];
$result = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $v = toFloat($_POST["voltage"] ?? null);
  $a = toFloat($_POST["current"] ?? null);
  $r = toFloat($_POST["rate"] ?? null);

  if ($v === null || $v <= 0) $errors[] = "Voltage must be a number greater than 0.";
  if ($a === null || $a <= 0) $errors[] = "Current must be a number greater than 0.";
  if ($r === null || $r <= 0) $errors[] = "Current rate must be a number greater than 0 (sen/kWh).";

  if (!$errors) {
    $voltage = $v;
    $current = $a;
    $rateSen = $r;

    $powerW = calcPowerW($voltage, $current);
    $powerKW = calcPowerKW($powerW);
    $rateRM = calcRateRM($rateSen);
    $rows = buildHourlyRows($powerKW, $rateRM, 24);

    $result = [
      "powerW" => $powerW,
      "powerKW" => $powerKW,
      "rateRM" => $rateRM,
      "rows" => $rows
    ];
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Electricity Calculator</title>

  <!-- Bootstrap 4 (CDN) -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

  <style>
    .result-box {
      border: 2px solid #bcdcff;
      border-radius: 6px;
      padding: 18px;
      background: #f7fbff;
    }
    .mono {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }
  </style>
</head>
<body class="bg-white">
  <div class="container py-5" style="max-width: 820px;">
    <div class="text-center mb-4">
      <h1 class="display-4 font-weight-bold">Calculate</h1>
      <div class="text-muted">Electricity power, energy and total charge</div>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" class="mb-4">
      <div class="form-group">
        <label class="font-weight-bold">Voltage</label>
        <input type="text" class="form-control" name="voltage" value="<?= htmlspecialchars((string)$voltage) ?>">
        <small class="form-text text-muted">Voltage (V)</small>
      </div>

      <div class="form-group">
        <label class="font-weight-bold">Current</label>
        <input type="text" class="form-control" name="current" value="<?= htmlspecialchars((string)$current) ?>">
        <small class="form-text text-muted">Ampere (A)</small>
      </div>

      <div class="form-group">
        <label class="font-weight-bold text-uppercase">Current Rate</label>
        <input type="text" class="form-control" name="rate" value="<?= htmlspecialchars((string)$rateSen) ?>">
        <small class="form-text text-muted">sen/kWh</small>
      </div>

      <button class="btn btn-outline-primary btn-lg px-5" type="submit">calculate</button>
    </form>

    <?php if ($result): ?>
      <div class="result-box mb-4">
        <div class="h5 font-weight-bold text-primary mono mb-3">
          POWER : <?= number_format($result["powerKW"], 5) ?> kw
        </div>
        <div class="h5 font-weight-bold text-primary mono mb-0">
          RATE : <?= number_format($result["rateRM"], 3) ?> RM
        </div>
      </div>

      <table class="table table-striped table-borderless">
        <thead class="border-bottom">
          <tr>
            <th style="width: 70px;">#</th>
            <th style="width: 120px;">Hour</th>
            <th>Energy (kWh)</th>
            <th class="text-right">TOTAL (RM)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($result["rows"] as $i => $row): ?>
            <tr>
              <td class="font-weight-bold"><?= $i + 1 ?></td>
              <td><?= $row["hour"] ?></td>
              <td><?= number_format($row["energy"], 5) ?></td>
              <td class="text-right"><?= number_format($row["total"], 2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="text-muted small">
        Notes:
        Power(W)=V×A, Power(kW)=W/1000, Energy(kWh)=Power(kW)×Hour, Total(RM)=Energy×(sen/kWh ÷ 100).
      </div>
    <?php else: ?>
      <div class="text-muted">
        Enter values and click <b>calculate</b> to see the hourly/day table (1–24 hours).
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
