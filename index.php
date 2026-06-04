<?php
include 'db.php';

$result = "";
$error = "";
$expressionValue = "";
$displayValue = "";

/* ================= VALIDATION FUNCTION ================= */
function isValidExpression($expr) {

    $expr = str_replace(' ', '', $expr);

    if ($expr === '') return false;

    if (preg_match('/[^0-9+\-*\/().%]/', $expr)) return false;

    if (preg_match('/[\+\-\*\/]{2,}/', $expr)) return false;

    if (preg_match('/^[\+\*\/]/', $expr)) return false;

    if (preg_match('/[\+\-\*\/]$/', $expr)) return false;

    return true;
}

/* ================= CALCULATION ================= */
if (isset($_POST['calculate'])) {

    $expression = trim($_POST['expression']);
    $expressionValue = $expression;

    $expression = str_replace("÷", "/", $expression);
    $expression = str_replace("×", "*", $expression);
    $expression = str_replace("−", "-", $expression);

    if (empty($expression)) {

        $error = "AI Engine: No input detected.";
        $displayValue = "";

    } elseif (!isValidExpression($expression)) {

        $error = "AI Engine: Invalid syntax detected.";
        $displayValue = $expressionValue;

    } else {

        try {

            $result = eval("return $expression;");

            if ($result !== FALSE) {

                $displayValue = $result;

                $stmt = $conn->prepare(
                    "INSERT INTO history (expression, result)
                    VALUES (?, ?)"
                );

                $stmt->bind_param("ss", $expressionValue, $result);
                $stmt->execute();

            } else {
                $error = "AI Engine: Calculation failed.";
                $displayValue = $expressionValue;
            }

        } catch (Throwable $e) {
            $error = "AI Engine: Processing error.";
            $displayValue = $expressionValue;
        }
    }
}

/* ================= CLEAR HISTORY ================= */
if (isset($_POST['clear_history'])) {
    $conn->query("TRUNCATE TABLE history");
    header("Location: index.php");
    exit();
}

$history = $conn->query("SELECT * FROM history ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>

<title>AI CALC CORE // NEURAL ENGINE</title>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">

<style>

/* (UNCHANGED DESIGN — EXACT SAME AS YOUR ORIGINAL) */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Orbitron',sans-serif;
}

body{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background: radial-gradient(circle at top,#102a54 0%,#0b1b3a 40%,#05060f 100%);
    overflow:hidden;
    padding:25px;
}

body::before{
    content:'';
    position:absolute;
    width:200%;
    height:200%;
    background:
        radial-gradient(circle at center, rgba(0,255,255,0.10) 1px, transparent 2px),
        radial-gradient(circle at center, rgba(170,120,255,0.08) 1px, transparent 2px),
        linear-gradient(180deg, rgba(0,255,255,0.04), transparent);
    background-size:70px 70px;
    animation:floatGrid 20s linear infinite;
    transform: rotate(25deg);
    opacity:0.95;
}

@keyframes floatGrid{
    from { transform: translateY(0) rotate(25deg); }
    to { transform: translateY(120px) rotate(25deg); }
}

.wrapper{
    display:flex;
    gap:40px;
    flex-wrap:wrap;
    justify-content:center;
    z-index:2;
}

.calculator{
    width:440px;
    padding:30px;
    border-radius:28px;
    background: rgba(10,15,35,0.75);
    border:1px solid rgba(0,255,255,0.15);
    backdrop-filter: blur(25px);
}

.title{
    text-align:center;
    font-size:22px;
    letter-spacing:4px;
    color:#7df9ff;
    margin-bottom:20px;
    text-shadow:0 0 12px #00ffff;
}

.screen{
    width:100%;
    height:100px;
    border:none;
    outline:none;
    border-radius:18px;
    padding:20px;
    margin-bottom:25px;
    background: rgba(0,0,0,0.6);
    color:#00f7ff;
    font-size:34px;
    text-align:right;
    box-shadow: inset 0 0 25px rgba(0,255,255,0.25);
}

.buttons{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:14px;
}

button{
    height:70px;
    border:none;
    border-radius:18px;
    cursor:pointer;
    font-size:24px;
    font-weight:900;
    color:#fff;
}

.number{ background:rgba(255,255,255,0.08); }
.operator{ background:linear-gradient(145deg,#00e5ff,#7c4dff); }
.equal{ grid-column:span 2; background:linear-gradient(145deg,#00ffb3,#00c853); }
.clear{ background: linear-gradient(145deg,#ff0033,#ff1744); }

.history-box{
    width:360px;
    max-height:760px;
    overflow-y:auto;
    padding:25px;
    border-radius:28px;
    background: rgba(10,15,35,0.75);
    border:1px solid rgba(255,255,255,0.08);
}

.history-item{
    padding:16px;
    margin-bottom:12px;
    border-radius:14px;
    background:rgba(0,0,0,0.25);
    border:1px solid rgba(0,255,255,0.08);
}

.expr{ color:#7df9ff; font-weight:700; }
.eq{ color:#fff; opacity:0.6; margin:0 6px; }

.res{
    color:#00b7ff;
    font-size:20px;
    font-weight:900;
    text-shadow:0 0 15px rgba(0,183,255,0.8);
    animation:scan 1.2s infinite;
}

@keyframes scan{
    0%{opacity:0.6;transform:scale(1);}
    50%{opacity:1;transform:scale(1.05);}
    100%{opacity:0.6;transform:scale(1);}
}

.error{
    text-align:center;
    color:#ff4d6d;
    margin-top:10px;
}

</style>
</head>

<body>

<div class="wrapper">

<div class="calculator">

<div class="title">AI CALC CORE</div>

<form method="POST">

<input type="text" name="expression" id="screen" class="screen"
value="<?php echo htmlspecialchars($displayValue); ?>">

<div class="buttons">

<button type="button" class="clear" onclick="clearScreen()">C</button>
<button type="button" class="operator" onclick="appendValue('(')">(</button>
<button type="button" class="operator" onclick="appendValue(')')">)</button>
<button type="button" class="operator" onclick="appendValue('/')">÷</button>

<button type="button" class="number" onclick="appendValue('7')">7</button>
<button type="button" class="number" onclick="appendValue('8')">8</button>
<button type="button" class="number" onclick="appendValue('9')">9</button>
<button type="button" class="operator" onclick="appendValue('*')">×</button>

<button type="button" class="number" onclick="appendValue('4')">4</button>
<button type="button" class="number" onclick="appendValue('5')">5</button>
<button type="button" class="number" onclick="appendValue('6')">6</button>
<button type="button" class="operator" onclick="appendValue('-')">−</button>

<button type="button" class="number" onclick="appendValue('1')">1</button>
<button type="button" class="number" onclick="appendValue('2')">2</button>
<button type="button" class="number" onclick="appendValue('3')">3</button>
<button type="button" class="operator" onclick="appendValue('+')">+</button>

<button type="button" class="number" onclick="appendValue('0')">0</button>
<button type="button" class="number" onclick="appendValue('.')">.</button>

<button type="submit" name="calculate" class="equal">=</button>

</div>

</form>

<?php if($error != ""): ?>
<div class="error"><?php echo $error; ?></div>
<?php endif; ?>

</div>

<div class="history-box">

<h2 style="color:#7df9ff;text-align:center;">AI MEMORY LOG</h2>

<form method="POST">
<button type="submit" name="clear_history" class="clear" style="width:100%;height:55px;margin-bottom:15px;">
RESET AI MEMORY
</button>
</form>

<?php
if($history->num_rows > 0){
while($row = $history->fetch_assoc()){
echo "<div class='history-item'>";
echo "<span class='expr'>".htmlspecialchars($row['expression'])."</span>";
echo " <span class='eq'>=</span> ";
echo "<span class='res'>".htmlspecialchars($row['result'])."</span>";
echo "</div>";
}
}
?>

</div>

</div>

<script>

const screen = document.getElementById("screen");

function appendValue(value){
    screen.value += value;
}

function clearScreen(){
    screen.value = "";
}

document.addEventListener("keydown", function(event){

    const key = event.key;

    const allowed = [
        "0","1","2","3","4","5","6","7","8","9",
        "+","-","*","/","(",")",".",
        "Backspace","Delete","Enter"
    ];

    if(key === "Enter"){
        event.preventDefault();
        document.querySelector('button[name="calculate"]').click();
    }

    if(!allowed.includes(key)){
        if(!event.ctrlKey && !event.metaKey){
            event.preventDefault();
        }
    }

});

window.onload = () => screen.focus();

</script>

</body>
</html>