<?php
function loadProcessedData($conn) {
    $data = [];
    $labels = [];

    $sql = "SELECT * FROM data_balita_processed";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            (int)$row['jenis_kelamin'],
            (float)$row['umur'],
            (float)$row['tinggi_cm'],
            (float)$row['tinggi_m'],
            (float)$row['tinggi_m2'],
            (float)$row['berat_kg'],
            (float)$row['imt']
        ];
        $labels[] = $row['status_gizi'];
    }

    return [$data, $labels];
}

function trainNaiveBayes($X_train, $y_train) {
    $model = [];
    $classes = array_unique($y_train);
    $num_features = count($X_train[0]);

    foreach ($classes as $class) {
        $class_data = [];
        for ($i = 0; $i < count($X_train); $i++) {
            if ($y_train[$i] === $class) {
                $class_data[] = $X_train[$i];
            }
        }

        $means = [];
        $variances = [];
        for ($j = 0; $j < $num_features; $j++) {
            $feature_values = array_column($class_data, $j);
            $mean = array_sum($feature_values) / count($feature_values);
            $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $feature_values)) / count($feature_values);
            $means[] = $mean;
            $variances[] = $variance ?: 1e-6; // Prevent division by zero
        }

        $model[$class] = [
            'prior' => count($class_data) / count($X_train),
            'mean' => $means,
            'variance' => $variances
        ];
    }

    return $model;
}

function gaussianProbability($x, $mean, $variance) {
    $exponent = exp(-pow($x - $mean, 2) / (2 * $variance));
    return (1 / sqrt(2 * pi() * $variance)) * $exponent;
}

function predict($model, $x) {
    $probabilities = [];

    foreach ($model as $class => $params) {
        $prior = log($params['prior']);
        $sum = $prior;

        for ($i = 0; $i < count($x); $i++) {
            $sum += log(gaussianProbability($x[$i], $params['mean'][$i], $params['variance'][$i]));
        }

        $probabilities[$class] = $sum;
    }

    arsort($probabilities);
    return array_key_first($probabilities);
}

function evaluateModel($model, $X_test, $y_test) {
    $y_pred = [];
    $labels = array_unique(array_merge($y_test));

    foreach ($X_test as $x) {
        $y_pred[] = predict($model, $x);
    }

    $confusion = [];
    foreach ($labels as $label) {
        foreach ($labels as $pred_label) {
            $confusion[$label][$pred_label] = 0;
        }
    }

    for ($i = 0; $i < count($y_test); $i++) {
        $confusion[$y_test[$i]][$y_pred[$i]]++;
    }

    $report = [];
    foreach ($labels as $label) {
        $tp = $confusion[$label][$label];
        $fp = array_sum(array_column($confusion, $label)) - $tp;
        $fn = array_sum($confusion[$label]) - $tp;
        $precision = $tp + $fp > 0 ? $tp / ($tp + $fp) : 0;
        $recall = $tp + $fn > 0 ? $tp / ($tp + $fn) : 0;
        $f1 = $precision + $recall > 0 ? 2 * ($precision * $recall) / ($precision + $recall) : 0;

        $report[$label] = [
            'precision' => round($precision, 2),
            'recall' => round($recall, 2),
            'f1_score' => round($f1, 2),
            'support' => array_sum($confusion[$label])
        ];
    }

    return [$report, $confusion];
}

function naive_bayes($conn) {
    list($data, $labels) = loadProcessedData($conn);

    $total = count($data);
    $train_size = (int)($total * 0.8);

    $X_train = array_slice($data, 0, $train_size);
    $y_train = array_slice($labels, 0, $train_size);
    $X_test = array_slice($data, $train_size);
    $y_test = array_slice($labels, $train_size);

    $model = trainNaiveBayes($X_train, $y_train);

    list($report, $confusion) = evaluateModel($model, $X_test, $y_test);

    // Hitung akurasi
    $correct = 0;
    for ($i = 0; $i < count($X_test); $i++) {
        $pred = predict($model, $X_test[$i]);
        if ($pred === $y_test[$i]) $correct++;
    }
    $accuracy = $correct / count($y_test);

    return [
        'classification_report' => $report,
        'confusion_matrix' => $confusion,
        'accuracy' => round($accuracy, 4)
    ];
}
?>
