// Inisialisasi TinyMCE untuk textarea
document.addEventListener("DOMContentLoaded", function() {
    tinymce.init({
        selector: 'textarea#deskripsi',
        plugins: 'lists link table',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link',
        height: 300,
        menubar: false
    });
});