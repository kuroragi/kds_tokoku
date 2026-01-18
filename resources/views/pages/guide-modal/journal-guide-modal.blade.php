<!-- Journal Guide Modal -->
<div class="modal fade" id="journalGuideModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ri-guide-line me-2"></i>
                    Panduan Entri Jurnal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <h6 class="fw-bold mb-3">
                            <i class="ri-information-line text-info"></i>
                            Memahami Entri Jurnal
                        </h6>
                        <p class="text-muted">
                            Entri jurnal adalah blok bangunan dasar akuntansi. Mereka mencatat
                            transaksi bisnis menggunakan sistem pembukuan double-entry, dimana setiap
                            transaksi mempengaruhi setidaknya dua akun dan total debit harus sama dengan total kredit.
                        </p>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-bold text-primary">
                                <i class="ri-add-circle-line"></i>
                                Membuat Entri Jurnal
                            </h6>
                            <ol class="mb-0 small">
                                <li class="mb-2">
                                    <strong>Tentukan Tanggal Jurnal:</strong>
                                    <span class="text-muted">Pilih tanggal transaksi</span>
                                </li>
                                <li class="mb-2">
                                    <strong>Pilih Periode:</strong>
                                    <span class="text-muted">Tetapkan ke periode akuntansi</span>
                                </li>
                                <li class="mb-2">
                                    <strong>Tambah Referensi:</strong>
                                    <span class="text-muted">Nomor referensi opsional</span>
                                </li>
                                <li class="mb-2">
                                    <strong>Masukkan Keterangan:</strong>
                                    <span class="text-muted">Jelaskan transaksi</span>
                                </li>
                                <li class="mb-2">
                                    <strong>Tambah Baris Jurnal:</strong>
                                    <span class="text-muted">Pilih akun dan jumlah</span>
                                </li>
                                <li>
                                    <strong>Pastikan Seimbang:</strong>
                                    <span class="text-muted">Total debit = Total kredit</span>
                                </li>
                            </ol>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-bold text-success">
                                <i class="ri-scales-line"></i>
                                Aturan Debit & Kredit
                            </h6>
                            <div class="row small">
                                <div class="col-6">
                                    <strong class="text-primary">Debit menambah:</strong>
                                    <ul class="list-unstyled mb-2">
                                        <li>• Aset</li>
                                        <li>• Beban</li>
                                        <li>• Penarikan</li>
                                    </ul>

                                    <strong class="text-danger">Kredit menambah:</strong>
                                    <ul class="list-unstyled mb-0">
                                        <li>• Kewajiban</li>
                                        <li>• Ekuitas</li>
                                        <li>• Pendapatan</li>
                                    </ul>
                                </div>
                                <div class="col-6">
                                    <div class="alert alert-warning py-2 px-2 small">
                                        <strong>Ingat:</strong><br>
                                        Setiap entri jurnal harus seimbang -
                                        jumlah debit harus sama dengan jumlah kredit.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-bold text-warning">
                                <i class="ri-file-list-3-line"></i>
                                Status Jurnal
                            </h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <span class="badge bg-warning me-2">Draft</span>
                                    <small class="text-muted">Dapat diedit, belum mempengaruhi buku besar</small>
                                </li>
                                <li class="mb-2">
                                    <span class="badge bg-success me-2">Posted</span>
                                    <small class="text-muted">Final, mempengaruhi buku besar umum</small>
                                </li>
                                <li>
                                    <span class="badge bg-danger me-2">Cancelled</span>
                                    <small class="text-muted">Entri dibatalkan</small>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-bold text-danger">
                                <i class="ri-bookmark-line"></i>
                                Contoh Entri
                            </h6>
                            <div class="small">
                                <strong>Pembelian Persediaan Tunai:</strong>
                                <table class="table table-sm mt-2 mb-0">
                                    <thead>
                                        <tr>
                                            <th>Akun</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Kredit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Persediaan</td>
                                            <td class="text-end">1,000,000</td>
                                            <td class="text-end">-</td>
                                        </tr>
                                        <tr>
                                            <td>Kas</td>
                                            <td class="text-end">-</td>
                                            <td class="text-end">1,000,000</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="ri-lightbulb-line"></i>
                                Praktik Terbaik
                            </h6>
                            <ul class="mb-0">
                                <li>Selalu sertakan narasi yang jelas dan deskriptif</li>
                                <li>Gunakan penomoran referensi yang konsisten untuk transaksi terkait</li>
                                <li>Tinjau entri jurnal sebelum posting untuk menghindari kesalahan</li>
                                <li>Simpan dokumen pendukung untuk audit trail</li>
                                <li>Posting jurnal dalam urutan kronologis jika memungkinkan</li>
                                <li>Gunakan Chart of Accounts yang sesuai untuk klasifikasi yang tepat</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ri-close-line"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>