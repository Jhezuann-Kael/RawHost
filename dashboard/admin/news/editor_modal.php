<!-- News Editor Modal Component -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<style>
    /* Custom Scrollbar para el modal */
    .modal-body::-webkit-scrollbar {
        width: 6px;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: #333;
        border-radius: 10px;
    }

    /* Estilos Dark para el Editor Quill */
    .ql-toolbar.ql-snow {
        background: #f0f0f0 !important;
        border: 1px solid #333 !important;
        border-radius: 8px 8px 0 0 !important;
    }

    .ql-container.ql-snow {
        background: #12121c !important;
        border: 1px solid #333 !important;
        border-radius: 0 0 8px 8px !important;
        min-height: 250px;
        font-size: 16px;
        color: #e0e0e0;
    }

    .ql-editor.ql-blank::before {
        color: #555 !important;
        font-style: normal;
    }

    .ql-snow .ql-stroke {
        stroke: #1e1e2d !important;
    }

    .ql-snow .ql-fill {
        fill: #1e1e2d !important;
    }

    .ql-snow .ql-picker {
        color: #1e1e2d !important;
    }

    /* Previsualización Render */
    #previewBody img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
    }

    #previewBody ul,
    #previewBody ol {
        padding-left: 20px;
    }

    #previewBody blockquote {
        border-left: 4px solid var(--primary);
        padding-left: 15px;
        color: #7f8c8d;
    }

    /* Ajuste para que el editor no se salga en móviles */
    .ql-toolbar.ql-snow {
        display: flex;
        flex-wrap: wrap;
    }
</style>

<div id="newsModal"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(8px);">
    <div class="modal-body"
        style="background: #1e1e2d; color: white; padding:25px; border-radius:12px; width:800px; max-width:95%; max-height: 90vh; overflow-y: auto; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 20px 50px rgba(0,0,0,0.6);">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 id="modalTitle" style="margin:0; font-size: 1.5rem;">Nueva Noticia</h3>
            <button onclick="closeNewsModal()"
                style="background:transparent; border:none; color:#7f8c8d; cursor:pointer; font-size:1.5rem;"><i
                    class="fas fa-times"></i></button>
        </div>

        <form id="newsForm">
            <input type="hidden" id="newsId">

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom:15px;">
                <div>
                    <label
                        style="display:block; margin-bottom:5px; font-weight:600; color: #b0bec5;">Título:</label>
                    <input type="text" id="newsTitle"
                        style="width:100%; box-sizing:border-box; padding:12px; background:#12121c; border:1px solid #333; border-radius:8px; color:white;"
                        required placeholder="Ej: Nueva actualización de red...">
                </div>
                <div>
                    <label
                        style="display:block; margin-bottom:5px; font-weight:600; color: #b0bec5;">Categoría:</label>
                    <select id="newsCategory"
                        style="width:100%; box-sizing:border-box; padding:12px; background:#12121c; border:1px solid #333; border-radius:8px; color:white;">
                        <option value="GENERAL">General</option>
                        <option value="MAINTENANCE">Mantenimiento</option>
                        <option value="NEW_SERVICE">Nuevo Servicio</option>
                        <option value="PROMOTION">Promoción</option>
                        <option value="ALERT">Alerta</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:5px; font-weight:600; color: #b0bec5;">Contenido:</label>
                <div id="editor-container"></div>
                <input type="hidden" id="newsContent">
            </div>

            <div style="margin-bottom:25px; background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px;">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <input type="checkbox" id="newsActive" checked style="width:18px; height:18px;">
                    <span style="font-weight:600; color: #b0bec5;">Hacer esta noticia visible ahora</span>
                </label>
            </div>

            <div
                style="text-align:right; display: flex; gap: 10px; justify-content: flex-end; margin-bottom: 30px;">
                <button type="button" onclick="closeNewsModal()"
                    style="padding:10px 20px; border:1px solid #444; background:transparent; color:white; border-radius:8px; cursor:pointer;">Cancelar</button>
                <button type="submit" id="saveBtn"
                    style="padding:10px 30px; border:none; background:var(--primary); color:white; border-radius:8px; cursor:pointer; font-weight: 600;">Publicar
                    Noticia</button>
            </div>

            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                <h4
                    style="margin: 0 0 15px 0; color: #7f8c8d; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px;">
                    Vista Previa en Vivo</h4>
                <div id="newsPreview"
                    style="background: #12121c; padding: 30px; border-radius: 12px; border: 1px solid #333;">
                    <span id="previewCategory"
                        style="font-size:0.7rem; background:var(--primary); color: white; padding:4px 10px; border-radius:4px; text-transform: uppercase; font-weight: 800; display: inline-block; margin-bottom: 10px;"></span>
                    <h2 id="previewTitle"
                        style="margin: 0 0 15px 0; font-size: 2rem; color: #fff; border-bottom: 1px solid #222; padding-bottom: 10px;">
                    </h2>
                    <div id="previewBody" style="color: #b0bec5; line-height: 1.8; font-size: 1rem;"></div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
    // Inicializar Quill con configuración robusta
    let quill;
    
    function initQuill() {
        if (quill) return;
        
        quill = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: 'Escribe algo increíble...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'color': [] }, { 'background': [] }],
                    ['link', 'image', 'code-block'],
                    ['clean']
                ]
            }
        });

        // Evento para actualizar preview y input oculto
        quill.on('text-change', () => {
            const html = quill.root.innerHTML;
            document.getElementById('newsContent').value = html;
            document.getElementById('previewBody').innerHTML = html;
        });
    }

    function updatePreviewText() {
        document.getElementById('previewTitle').innerText = document.getElementById('newsTitle').value || 'Título de la Noticia';
        document.getElementById('previewCategory').innerText = document.getElementById('newsCategory').value;
    }

    function openNewsModal() {
        initQuill();
        document.getElementById('newsForm').reset();
        document.getElementById('newsId').value = '';
        quill.root.innerHTML = '';
        document.getElementById('modalTitle').innerText = 'Nueva Noticia';
        updatePreviewText();
        document.getElementById('newsModal').style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Evitar scroll de fondo
    }

    function editNews(item) {
        initQuill();
        document.getElementById('newsId').value = item.id;
        document.getElementById('newsTitle').value = item.title;
        document.getElementById('newsCategory').value = item.category;
        quill.root.innerHTML = item.content;
        document.getElementById('newsActive').checked = item.is_active == 1;

        updatePreviewText();
        document.getElementById('modalTitle').innerText = 'Editar Noticia';
        document.getElementById('newsModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeNewsModal() {
        document.getElementById('newsModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    // Cerrar modal al hacer click fuera
    window.onclick = function(event) {
        const modal = document.getElementById('newsModal');
        if (event.target == modal) {
            closeNewsModal();
        }
    }
</script>
