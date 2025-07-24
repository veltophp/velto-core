<?php

function VeltoImage(string $selector, ?string $existingImageUrl = null): string
{
    $id = trim($selector, '#');
    $previewId = $id . '_preview';
    $wrapperId = $id . '_wrapper';
    $plusId = $id . '_plus';
    $removeId = $id . '_remove';
    $modalId = $id . '_modal';
    $modalImgId = $id . '_modal_img';

    ob_start(); ?>
    
    <style>
        #<?= $wrapperId ?> {
            border: 2px dashed #cccddd;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            border-radius: 6px;
            background-color: #fff;
            position: relative;
            transition: border-color 0.3s;
            width: 100px;
            height: 100px;
            margin-top: 8px;
        }

        #<?= $wrapperId ?>:hover {
            border-color: #b91c1c;
        }

        .velto-plus {
            font-size: 28px;
            color: #b91c1c;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .velto-remove {
            position: absolute;
            top: 4px;
            right: 4px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            text-align: center;
            line-height: 24px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            display: none;
        }

        #<?= $previewId ?> {
            max-width: 80%;
            max-height: 100%;
            display: <?= $existingImageUrl ? 'block' : 'none' ?>;
            margin-top: 4px;
            object-fit: contain;
            cursor: zoom-in;
        }

        #<?= $wrapperId ?>.has-image .velto-plus {
            display: none;
        }

        #<?= $wrapperId ?>.has-image:hover .velto-remove {
            display: block;
        }

        /* Modal preview style */
        #<?= $modalId ?> {
            display: none;
            position: fixed;
            z-index: 9999;
            padding-top: 60px;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            overflow: auto;
            background-color: rgba(0,0,0,0.8);
        }

        #<?= $modalImgId ?> {
            margin: auto;
            display: block;
            max-width: 90vw;
            max-height: 80vh;
        }

        #<?= $modalId ?>:target {
            display: block;
        }

        .velto-modal-close {
            position: absolute;
            top: 30px;
            right: 30px;
            color: #fff;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
        }

    </style>

    <div id="<?= $wrapperId ?>"
         class="<?= $existingImageUrl ? 'has-image' : '' ?>"
         onclick="document.getElementById('<?= $id ?>').click()"
         ondragover="event.preventDefault(); this.style.borderColor='#b91c1c';"
         ondragleave="this.style.borderColor='#cccddd';"
         ondrop="handleDrop(event, '<?= $id ?>', '<?= $previewId ?>', '<?= $wrapperId ?>')">

        <div id="<?= $plusId ?>" class="velto-plus">
            <svg width="50px" height="50px" viewBox="0 0 24 24" fill="none"
                 xmlns="http://www.w3.org/2000/svg">
                <path d="M14.2647 15.9377L12.5473 14.2346C11.758 13.4519 11.3633 13.0605 10.9089 12.9137C10.5092 12.7845 10.079 12.7845 9.67922 12.9137C9.22485 13.0605 8.83017 13.4519 8.04082 14.2346L4.04193 18.2622M14.2647 15.9377L14.606 15.5991C15.412 14.7999 15.8149 14.4003 16.2773 14.2545C16.6839 14.1262 17.1208 14.1312 17.5244 14.2688C17.9832 14.4253 18.3769 14.834 19.1642 15.6515L20 16.5001M14.2647 15.9377L18.22 19.9628M18.22 19.9628C17.8703 20 17.4213 20 16.8 20H7.2C6.07989 20 5.51984 20 5.09202 19.782C4.7157 19.5903 4.40973 19.2843 4.21799 18.908C4.12583 18.7271 4.07264 18.5226 4.04193 18.2622M18.22 19.9628C18.5007 19.9329 18.7175 19.8791 18.908 19.782C19.2843 19.5903 19.5903 19.2843 19.782 18.908C20 18.4802 20 17.9201 20 16.8V13M11 4H7.2C6.07989 4 5.51984 4 5.09202 4.21799C4.7157 4.40973 4.40973 4.71569 4.21799 5.09202C4 5.51984 4 6.0799 4 7.2V16.8C4 17.4466 4 17.9066 4.04193 18.2622M18 9V6M18 6V3M18 6H21M18 6H15"
                      stroke="#b91c1c" stroke-width="1" stroke-linecap="round"
                      stroke-linejoin="round"/>
            </svg>
        </div>

        <img id="<?= $previewId ?>" src="<?= htmlspecialchars($existingImageUrl ?? '', ENT_QUOTES) ?>"
             onclick="openImageModal(event, '<?= $modalId ?>', '<?= $modalImgId ?>')">

        <div id="<?= $removeId ?>" class="velto-remove"
             onclick="removeImage(event, '<?= $id ?>', '<?= $previewId ?>', '<?= $wrapperId ?>')">×</div>
    </div>

    <div id="<?= $modalId ?>">
        <span class="velto-modal-close" onclick="closeImageModal('<?= $modalId ?>')">&times;</span>
        <img id="<?= $modalImgId ?>">
    </div>

    <script>
        function handleDrop(e, inputId, previewId, wrapperId) {
            e.preventDefault();
            const file = e.dataTransfer.files[0];
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const wrapper = document.getElementById(wrapperId);

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            input.files = dataTransfer.files;

            const reader = new FileReader();
            reader.onload = function (evt) {
                preview.src = evt.target.result;
                preview.style.display = 'block';
                wrapper.classList.add('has-image');
            };
            reader.readAsDataURL(file);
        }

        function removeImage(e, inputId, previewId, wrapperId) {
            e.stopPropagation();
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const wrapper = document.getElementById(wrapperId);

            input.value = '';
            preview.src = '';
            preview.style.display = 'none';
            wrapper.classList.remove('has-image');
        }

        function openImageModal(e, modalId, imgId) {
            e.stopPropagation();
            const modal = document.getElementById(modalId);
            const modalImg = document.getElementById(imgId);
            modal.style.display = "block";
            modalImg.src = e.target.src;
        }

        function closeImageModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        document.getElementById('<?= $id ?>').addEventListener('change', function (e) {
            const file = e.target.files[0];
            const preview = document.getElementById('<?= $previewId ?>');
            const wrapper = document.getElementById('<?= $wrapperId ?>');
            if (file) {
                const reader = new FileReader();
                reader.onload = function (evt) {
                    preview.src = evt.target.result;
                    preview.style.display = 'block';
                    wrapper.classList.add('has-image');
                };
                reader.readAsDataURL(file);
            }
        });
    </script>

    <?php
    return ob_get_clean();
}
