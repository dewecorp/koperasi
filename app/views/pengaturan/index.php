<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h2 class="font-semibold text-slate-800">Pengaturan Aplikasi</h2>
                <p class="text-sm text-slate-500">Hanya Administrator dapat mengubah pengaturan.</p>
            </div>
            <button type="button" onclick="openPengaturanModal()" class="btn btn-primary shrink-0"><?= icon('edit', 'w-4 h-4') ?> Ubah</button>
        </div>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl"><span class="text-slate-500">Tahun Ajaran Aktif</span><b class="text-slate-800"><?= e($set['tahun_ajaran_aktif']) ?></b></div>
            <div class="flex justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl"><span class="text-slate-500">Izinkan Saldo Kas Negatif</span><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $set['allow_negative_cash']==='1'?'bg-emerald-100 text-emerald-700':'bg-slate-200 text-slate-600' ?>"><?= $set['allow_negative_cash']==='1'?'Ya':'Tidak' ?></span></div>
            <div class="flex justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl"><span class="text-slate-500">Izinkan Stok Negatif</span><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $set['allow_negative_stock']==='1'?'bg-emerald-100 text-emerald-700':'bg-slate-200 text-slate-600' ?>"><?= $set['allow_negative_stock']==='1'?'Ya':'Tidak' ?></span></div>
            <div class="flex justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl"><span class="text-slate-500">Batas Minimum Kas</span><b class="text-slate-800"><?= rupiah((float)$set['saldo_minimum_cash']) ?></b></div>
        </div>
    </div>

    <?php
    $tahunHapusList = array_filter(array_merge($tahunOptions, array_column($tahunBerisi, 'tahun_ajaran')), fn($t) => $t !== $set['tahun_ajaran_aktif']);
    $tahunHapusList = array_values(array_unique($tahunHapusList));
    sort($tahunHapusList);
    ?>
    <div class="bg-white rounded-xl shadow-sm border border-red-200 p-6">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h2 class="font-semibold text-red-700">Hapus Data per Tahun Ajaran</h2>
                <p class="text-sm text-slate-500">Hapus transaksi tahun ajaran terpilih (permanen).</p>
            </div>
            <?php if (!empty($tahunHapusList)): ?>
                <button type="button" onclick="openHapusTahunModal()" class="btn bg-red-600 text-white hover:bg-red-700 shrink-0"><?= icon('trash', 'w-4 h-4') ?> Hapus Data</button>
            <?php endif; ?>
        </div>
        <?php if (empty($tahunHapusList)): ?>
            <div class="text-sm text-slate-400">Belum ada data tahun ajaran untuk dihapus.</div>
        <?php else: ?>
            <div class="text-sm text-slate-500">Pilih tahun ajaran untuk dihapus via tombol di atas. Tahun aktif <b><?= e($set['tahun_ajaran_aktif']) ?></b> tidak dapat dihapus.</div>
        <?php endif; ?>
    </div>
</div>

<div id="pengaturanModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closePengaturanModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-slate-800">Ubah Pengaturan</h3>
                <button type="button" onclick="closePengaturanModal()" class="h-8 w-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-500">&times;</button>
            </div>
            <form method="post" action="<?= url('pengaturan') ?>" class="space-y-4">
                <?= csrf_field() ?>
                <div class="p-4 border border-slate-200 rounded-xl">
                    <label class="label">Tahun Ajaran Aktif</label>
                    <select name="tahun_ajaran_aktif" class="input">
                        <?php $allTahun=array_values(array_unique(array_merge($tahunOptions,array_column($tahunBerisi,'tahun_ajaran')))); sort($allTahun); foreach($allTahun as $opt): ?>
                            <option value="<?= e($opt) ?>" <?= $set['tahun_ajaran_aktif']===$opt?'selected':'' ?>><?= e($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-slate-500 mt-1">Pindah tahun ajaran akan membawa saldo kas tahun sebelumnya sebagai saldo awal.</p>
                </div>
                <div class="flex items-start justify-between gap-4 p-4 border border-slate-200 rounded-xl">
                    <div><div class="font-medium text-slate-800 text-sm">Izinkan Saldo Kas Negatif</div><p class="text-xs text-slate-500">Matikan untuk menolak pengeluaran melebihi kas.</p></div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0"><input type="checkbox" name="allow_negative_cash" value="1" class="sr-only peer" <?= $set['allow_negative_cash']==='1'?'checked':'' ?>><div class="w-11 h-6 bg-slate-300 peer-checked:bg-emerald-600 rounded-full transition relative after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition peer-checked:after:translate-x-5"></div></label>
                </div>
                <div class="flex items-start justify-between gap-4 p-4 border border-slate-200 rounded-xl">
                    <div><div class="font-medium text-slate-800 text-sm">Izinkan Stok Negatif</div><p class="text-xs text-slate-500">Matikan untuk menolak penjualan stok kurang.</p></div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0"><input type="checkbox" name="allow_negative_stock" value="1" class="sr-only peer" <?= $set['allow_negative_stock']==='1'?'checked':'' ?>><div class="w-11 h-6 bg-slate-300 peer-checked:bg-emerald-600 rounded-full transition relative after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition peer-checked:after:translate-x-5"></div></label>
                </div>
                <div class="p-4 border border-slate-200 rounded-xl">
                    <label class="label">Batas Minimum Saldo Kas</label>
                    <input type="text" name="saldo_minimum_cash" class="input" inputmode="numeric" value="<?= e($set['saldo_minimum_cash']) ?>">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closePengaturanModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($tahunHapusList)): ?>
<div id="hapusTahunModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeHapusTahunModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-red-700">Hapus Data Tahun Ajaran</h3>
                <button type="button" onclick="closeHapusTahunModal()" class="h-8 w-8 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-500">&times;</button>
            </div>
            <p class="text-sm text-slate-500 mb-4">Data yang dihapus <b>tidak dapat dikembalikan</b>. Tahun aktif tidak dapat dihapus.</p>
            <form method="post" action="<?= url('pengaturan', ['action' => 'hapusTahunAjaran']) ?>" class="space-y-3">
                <?= csrf_field() ?>
                <div>
                    <label class="label">Tahun Ajaran</label>
                    <select name="tahun_ajaran_hapus" class="input" required>
                        <option value="">- Pilih tahun ajaran -</option>
                        <?php foreach($tahunHapusList as $t): $jml=0; foreach($tahunBerisi as $tb) if($tb['tahun_ajaran']===$t) $jml=(int)$tb['jml']; ?>
                            <option value="<?= e($t) ?>"><?= e($t) ?> <?= $jml>0?'('.$jml.' transaksi)':'(kosong)' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeHapusTahunModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn bg-red-600 text-white hover:bg-red-700" onclick="return appConfirmSubmit(event, 'Seluruh data transaksi tahun ajaran ini akan dihapus permanen dan tidak bisa dikembalikan. Lanjutkan?', 'Hapus Data')"><?= icon('trash', 'w-4 h-4') ?> Hapus Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function openPengaturanModal(){ document.getElementById('pengaturanModal').classList.remove('hidden'); }
function closePengaturanModal(){ document.getElementById('pengaturanModal').classList.add('hidden'); }
function openHapusTahunModal(){ var m=document.getElementById('hapusTahunModal'); if(m) m.classList.remove('hidden'); }
function closeHapusTahunModal(){ var m=document.getElementById('hapusTahunModal'); if(m) m.classList.add('hidden'); }
document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ closePengaturanModal(); closeHapusTahunModal(); }});
</script>
<noscript>
<div class="mt-6 bg-white rounded-xl shadow-sm border border-slate-200 p-6">
    <form method="post" action="<?= url('pengaturan') ?>" class="space-y-4">
        <?= csrf_field() ?>
        <select name="tahun_ajaran_aktif" class="input"><?php $allTahun=array_values(array_unique(array_merge($tahunOptions,array_column($tahunBerisi,'tahun_ajaran')))); sort($allTahun); foreach($allTahun as $opt): ?><option value="<?= e($opt) ?>" <?= $set['tahun_ajaran_aktif']===$opt?'selected':'' ?>><?= e($opt) ?></option><?php endforeach; ?></select>
        <input type="text" name="saldo_minimum_cash" class="input" value="<?= e($set['saldo_minimum_cash']) ?>">
        <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
    </form>
</div>
</noscript>
