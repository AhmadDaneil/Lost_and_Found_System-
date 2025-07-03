<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Found Item - FoundIt</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet"/>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background-color: #f5ff9c;
      padding: 20px;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .logo {
      font-size: 32px;
      font-weight: 800;
      text-shadow: 2px 2px 2px rgba(0, 0, 0, 0.1);
    }

    .icons {
      display: flex;
      gap: 20px;
    }

    .icon-btn {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background-color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid #000;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .icon-btn:hover {
      background-color: #000;
      transform: scale(1.1);
    }

    .icon-btn:hover svg {
      fill: #f5ff9c;
    }

    .icon-btn svg {
      width: 20px;
      height: 20px;
      fill: #000;
      transition: fill 0.3s ease;
    }

    .content-box {
      background-color: #fffdd0;
      padding: 30px;
      border-radius: 16px;
      max-width: 900px;
      margin: 0 auto;
      position: relative;
    }

    .back-btn {
      position: absolute;
      top: 20px;
      left: 20px;
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background-color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid #000;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .back-btn:hover {
      background-color: #000;
      transform: scale(1.1);
    }

    .back-btn:hover svg {
      fill: #f5ff9c;
    }

    .back-btn svg {
      width: 20px;
      height: 20px;
      fill: #000;
      transition: fill 0.3s ease;
    }

    .section-title {
      text-align: center;
      font-weight: 600;
      margin-bottom: 30px;
      font-size: 20px;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .form-section {
      display: flex;
      gap: 30px;
      flex-wrap: wrap;
    }

    .image-box {
      background-color: #8b1e1e;
      color: #fff;
      width: 180px;
      height: 200px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      border-radius: 12px;
      overflow: hidden;
      cursor: pointer;
      position: relative;
    }

    .image-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    #imageInput {
      display: none;
    }

    .details-box {
      flex: 1;
      background-color: white;
      padding: 20px;
      border-radius: 12px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .details-box input,
    .details-box select,
    textarea {
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 14px;
      width: 100%;
    }

    .details-date {
      text-align: right;
      font-size: 14px;
      color: #666;
    }

    .description-box,
    .status-box {
      background-color: white;
      padding: 20px;
      border-radius: 12px;
    }

    textarea {
      min-height: 80px;
      resize: vertical;
    }

    .status-box select {
      width: 100%;
      padding: 10px;
      font-size: 14px;
    }

    .update-btn {
      padding: 10px 20px;
      background-color: #8b1e1e;
      color: white;
      font-weight: 600;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      width: fit-content;
      align-self: flex-end;
    }

    .update-btn:hover {
      background-color: #6a1515;
    }
  </style>
</head>
<body>

  <div class="header">
    <div class="logo">FoundIt</div>
    <div class="icons">
      <a href="homepage.html" class="icon-btn" title="Home">
        <svg viewBox="0 0 24 24">
          <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1h-6v-6H10v6H4a1 1 0 0 1-1-1V9.5z"/>
        </svg>
      </a>
    </div>
  </div>

  <div class="content-box">
    <a href="found_item_view.html" class="back-btn" title="Back">
      <svg viewBox="0 0 24 24">
        <path d="M15 18l-6-6 6-6"/>
      </svg>
    </a>

    <div class="section-title">Found Item</div>

    <form onsubmit="submitForm(event)">
      <div class="form-section">
        <label for="imageInput" class="image-box" id="imagePreview">
          Click to upload image
          <input type="file" accept="image/*" id="imageInput" onchange="previewImage(event)" />
        </label>

        <div class="details-box">
          <input type="text" id="itemName" placeholder="Item Name" required />
          <input type="text" id="category" placeholder="Category" required />
          <input type="text" id="location" placeholder="Found Location" required />
          <div class="details-date" id="lastUpdated">Last updated: -</div>
        </div>
      </div>

      <div class="description-box">
        <textarea id="description" placeholder="Description..." required></textarea>
      </div>

      <div class="status-box">
        <select id="status">
          <option value="not_found">❌ Unclaimed</option>
          <option value="found">✅ Claimed by owner</option>
        </select>
      </div>

      <button type="submit" class="update-btn">Update</button>
    </form>
  </div>

  <script>
    function previewImage(event) {
      const file = event.target.files[0];
      const reader = new FileReader();

      reader.onload = function () {
        const imageBox = document.getElementById('imagePreview');
        imageBox.innerHTML = '<img src="' + reader.result + '" alt="Uploaded Image">';
      };

      if (file) {
        reader.readAsDataURL(file);
      }
    }

    function submitForm(event) {
      event.preventDefault();

      const date = new Date();
      const formattedDate = `${date.getFullYear()}/${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}`;

      document.getElementById('lastUpdated').innerText = `Last updated: ${formattedDate}`;
      alert("Form updated successfully on " + formattedDate);
    }
  </script>

</body>
</html>
