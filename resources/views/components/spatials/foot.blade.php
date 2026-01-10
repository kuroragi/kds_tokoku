<!-- Vendor js -->
<script src="/assets/js/vendor.min.js"></script>

<!-- Daterangepicker js -->
<script src="/assets/vendor/daterangepicker/moment.min.js"></script>
<script src="/assets/vendor/daterangepicker/daterangepicker.js"></script>

<!-- Apex Charts js -->
<script src="/assets/vendor/apexcharts/apexcharts.min.js"></script>

<!-- Vector Map js -->
<script src="/assets/vendor/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js"></script>
<script src="/assets/vendor/admin-resources/jquery.vectormap/maps/jquery-jvectormap-world-mill-en.js"></script>

<!-- Datatables js -->
<script src="/assets/vendor/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="/assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
<script src="/assets/vendor/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="/assets/vendor/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js"></script>
<script src="/assets/vendor/datatables.net-fixedcolumns-bs5/js/fixedColumns.bootstrap5.min.js"></script>
<script src="/assets/vendor/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js"></script>
<script src="/assets/vendor/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="/assets/vendor/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js"></script>
<script src="/assets/vendor/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script src="/assets/vendor/datatables.net-buttons/js/buttons.flash.min.js"></script>
<script src="/assets/vendor/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="/assets/vendor/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
<script src="/assets/vendor/datatables.net-select/js/dataTables.select.min.js"></script>

<!--  Select2 Plugin Js -->
<script src="/assets/vendor/select2/js/select2.min.js"></script>
<script src="/assets/js/pages/form-select2.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Datatable Demo Aapp js -->
<script src="/assets/js/pages/datatable.init.js"></script>

<!-- Dashboard App js -->
<script src="/assets/js/pages/dashboard.js"></script>

<!-- Remixicons Icons Demo js -->
<script src="/assets/js/pages/icons-bootstrap.init.js"></script>

<!-- App js -->
<script src="/assets/js/app.min.js"></script>

<!-- Sortable -->
<script src="/assets/js/jquery-sortable.min.js"></script>
<script src="/assets/js/alpine.cdn.min.js" defer></script>

{{-- <script>
    const exampleModal = document.getElementById('exampleModal')
            exampleModal.addEventListener('show.bs.modal', event => {
                // Button that triggered the modal
                const button = event.relatedTarget
                // Extract info from data-bs-* attributes
                const recipient = button.getAttribute('data-bs-whatever')
                // If necessary, you could initiate an AJAX request here
                // and then do the updating in a callback.
                //
                // Update the modal's content.
                const modalTitle = exampleModal.querySelector('.modal-title')
                const modalBodyInput = exampleModal.querySelector('.modal-body input')

                modalTitle.textContent = `New message to ${recipient}`
                modalBodyInput.value = recipient
            })
</script> --}}

@stack('scripts')
