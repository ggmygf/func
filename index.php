<?php
$uploadDir = "upls/";

if (!is_dir($uploadDir)) {mkdir($uploadDir, 0777, true);}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_GET["action"]) && $_GET["action"] === "delete_all") {
        // **DELETE ALL FILES**
        $files = array_diff(scandir($uploadDir), array('.', '..'));
        foreach ($files as $file) {unlink($uploadDir . $file);}
        echo json_encode(["status" => "success", "message" => "All files deleted!"]);
        exit;
    }

    // **HANDLE MULTIPLE FILE UPLOADS**
    $responses = [];

    foreach ($_FILES["files"]["tmp_name"] as $index => $tmpName) {
        if ($_FILES["files"]["error"][$index] == 0) {
            $fileExt = pathinfo($_FILES["files"]["name"][$index], PATHINFO_EXTENSION);
            $randomName = substr(str_shuffle("0123456789"), 0, 10) . "." . $fileExt;
            $targetPath = $uploadDir . $randomName;

            if (move_uploaded_file($tmpName, $targetPath)) {$responses[] = ["status" => "success", "filename" => $randomName];} 
            else {$responses[] = ["status" => "error", "message" => "Upload failed for file: " . $_FILES["files"]["name"][$index]];}
        
        } else {$responses[] = ["status" => "error", "message" => "Error in file upload!"];}
    }

    echo json_encode($responses);
} elseif ($_SERVER["REQUEST_METHOD"] === "GET") {
    // **FETCH PHOTOS**
    $files = array_diff(scandir($uploadDir), array('.', '..'));
    echo json_encode(array_values($files));
}
?>







<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Sharing</title>
    <style>
        body {
            background: #121212;
            color: #ffffff;
            text-align: center;
            font-family: Arial, sans-serif;
            margin: 0;
        }
        #progress {
            position: fixed;
            top: 0;
            left: 0;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            width: 0;
            transition: width 0.2s;
        }
        #gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            padding: 20px;
            justify-items: center;
        }
        img {
            width: 100%;
            max-width: 150px;
            height: auto;
            border-radius: 10px;
        }
        .controls {
            margin: 20px 0;
        }
        #dropOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(128, 128, 128, 0.7);
            color: white;
            font-size: 50px;
            font-weight: bold;
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
                  #toast {
    position: fixed; /* Stay in place */
    top: 20px; /* Adjust vertical position */
    left: 50%; /* Center horizontally */
    transform: translateX(-50%); /* Adjust centering */
    background-color: rgba(0, 0, 0, 0.8); /* Black with opacity */
    color: white;
    padding: 10px 15px;
    border-radius: 5px;
    opacity: 0; /* Initially hidden */
    transition: opacity 0.5s ease-in-out; /* Smooth fade effect */
    z-index: 1000; /* Ensure it's on top */
  }
          #toast.show {
    opacity: 1; /* Show the toast */
  }
    </style>
</head>
<body>
    <div id="progress"></div>
    <div id="dropOverlay">DROP!</div>
    <h1>FILE SHARING</h1>
    <div class="controls">
        <input type="file" id="fileInput" multiple>
        <button onclick="uploadPhoto()">上传</button>
        <button onclick="fetchPhotos()">刷新</button>
        <button onclick="deleteAllPhotos()">删除全部</button>
        <button onclick="downloadAllPhotos()">下载全部</button>
        <div id="toast"></div>
    </div>

    <div id="gallery"></div>

    <script>
let dropOverlay = document.getElementById("dropOverlay");

let downloadedPhotos = []; // Store the photos already fetched via "刷新"

function fetchPhotos() {
    let progressBar = document.getElementById("progress");
    progressBar.style.width = "0%";

    let xhr = new XMLHttpRequest();
    xhr.open("GET", "pic.php", true);
    xhr.onprogress = function (event) {
        if (event.lengthComputable) {
            let percent = (event.loaded / event.total) * 100;
            progressBar.style.width = percent + "%";
        }
    };
    xhr.onload = function () {
        if (xhr.status == 200) {
            downloadedPhotos = JSON.parse(xhr.responseText);
            let gallery = document.getElementById("gallery");
            gallery.innerHTML = "";

            downloadedPhotos.forEach(photo => {
                let img = document.createElement("img");
                img.src = "uploads/" + photo;
                img.alt = "Photo";
                gallery.appendChild(img);
            });
        }
        progressBar.style.width = "0%";
    };
    xhr.send();
}

function deleteAllPhotos() {
    if (!confirm("确定要删除所有照片吗？")) return;

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "pic.php?action=delete_all", true);
    xhr.onload = function () {
        if (xhr.status == 200) {
            alert("所有照片已删除!");
            fetchPhotos(); // Refresh UI
        }
    };
    xhr.send();
}

function downloadAllPhotos() {
    if (downloadedPhotos.length === 0) {
        alert("没有可下载的照片!");
        return;
    }

    downloadedPhotos.forEach(photo => {
        let link = document.createElement("a");
        link.href = "uploads/" + photo;
        link.download = photo;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
}


function uploadPhoto(files) {
    let progressBar = document.getElementById("progress");
    let formData = new FormData();

    if (files instanceof FileList) {
        for (let file of files) {
            formData.append("files[]", file); // ✅ Append all files
        }
    } else {
        formData.append("files[]", files); // ✅ Single file case
    }

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "pic.php", true);
    xhr.upload.onprogress = function (event) {
        if (event.lengthComputable) {
            let percent = (event.loaded / event.total) * 100;
            progressBar.style.width = percent + "%";
        }
    };
    xhr.onload = function () {
        if (xhr.status == 200) {
            showToast('good!');
        }
        progressBar.style.width = "0%";
    };
    xhr.send(formData);
}

        function showToast(message) {
      const toast = document.getElementById("toast");
      toast.textContent = message;
      toast.classList.add("show");

      setTimeout(() => {
        toast.classList.remove("show");
      }, 1000); // Hide after 1 second (1000 milliseconds)
    }  



        document.addEventListener("dragenter", (event) => {
            event.preventDefault();
            dropOverlay.style.display = "flex";
        });

        document.addEventListener("dragover", (event) => {
            event.preventDefault();
        });

        document.addEventListener("dragleave", (event) => {
            if (event.relatedTarget === null) {
                dropOverlay.style.display = "none";
            }
        });

document.addEventListener("drop", (event) => {
    event.preventDefault();
    dropOverlay.style.display = "none";

    if (event.dataTransfer.files.length > 0) {
        uploadPhoto(event.dataTransfer.files); // ✅ Send all dropped files
    }
});

        document.addEventListener("dragstart", (event) => {
            event.preventDefault();
        });

        document.addEventListener("dragend", (event) => {
            event.preventDefault();
            dropOverlay.style.display = "none";
        });
    </script>

</body>
</html>
