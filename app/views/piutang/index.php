<?php
$piutangUpdateTpl = url('piutang', ['action' => 'update', 'id' => '__ID__']);
?>
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between mb-4">
        <form method="get" action="<?= url('piutang') ?>" class="js-filter-form flex flex-col md:flex-row gap-2 flex-1">
            <input type="hidden" name="page" value="piutang">
            <div class="relative flex-1 max-w-md">
                <?= icon('search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400') ?>
                <input type="text" name="q" value="<?= e($q) ?>" class="input pl-9" placeholder="Cari pelanggan / no transaksi...">
            </div>
            <select name="status" class="input w-full md:w-40">
                <option value="">Semua Status</option>
                <option value="belum" <?= $status === 'belum' ? 'selected' : '' ?>>Belum Lunas</option>
                <option value="sebagian" <?= $status === 'sebagian' ? 'selected' : '' ?>>Sebagian</option>
                <option value="lunas" <?= $status === 'lunas' ? 'selected' : '' ?>>Lunas</option>
            </select>
        </form>
        <?php
        $totSisa = array_sum(array_map(fn($r) => (float)$r['sisa'], $pg['items']));
        ?>
        <div class="text-sm font-medium">Total Sisa Piutang (filter): <b class="text-fuchsia-600"><?= rupiah($totSisa) ?></b></div>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th>Pelanggan</th><th class="text-right">Total</th><th class="text-right">Dibayar</th><th class="text-right">Sisa</th><th>Jatuh Tempo</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($pg['items'])): ?>
                <tr><td colspan="9" class="text-center text-slate-400 py-8">Tidak ada piutang.</td></tr>
            <?php else: foreach ($pg['items'] as $r): list($label, $cls) = status_tagihan((float)$r['dibayar'], (float)$r['sisa']); ?>
                <?php $ptRowJson = htmlspecialchars(json_encode(['id' => $r['id'], 'no_transaksi' => $r['no_transaksi'], 'pelanggan' => $r['pelanggan'], 'total' => $r['total'], 'jatuh_tempo' => $r['jatuh_tempo']], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
                <tr>
                    <td class="font-mono text-xs"><?= e($r['no_transaksi']) ?></td>
                    <td class="whitespace-nowrap"><?= tanggal($r['tanggal']) ?></td>
                    <td><?= e($r['pelanggan']) ?></td>
                    <td class="text-right"><?= rupiah($r['total']) ?></td>
                    <td class="text-right text-emerald-600"><?= rupiah($r['dibayar']) ?></td>
                    <td class="text-right font-bold text-fuchsia-600"><?= rupiah($r['sisa']) ?></td>
                    <td class="whitespace-nowrap <?= $r['jatuh_tempo'] && $r['jatuh_tempo'] < date('Y-m-d') && $r['sisa'] > 0 ? 'text-red-600 font-medium' : '' ?>"><?= tanggal($r['jatuh_tempo']) ?></td>
                    <td><span class="text-[11px] px-2 py-0.5 rounded-full font-medium <?= $cls ?>"><?= e($label) ?></span></td>
                    <td>
                        <div class="flex items-center justify-center gap-1">
                            <a href="<?= url('piutang', ['action' => 'show', 'id' => $r['id']]) ?>" class="btn btn-ghost p-1.5" title="Detail & Bayar"><?= icon('eye', 'w-4 h-4') ?></a>
                            <?php if (has_role('Administrator') || has_role('Bendahara')): ?>
                                <button type="button" onclick="openPiutangEditModal(JSON.parse(this.dataset.row))" data-row="<?= $ptRowJson ?>" class="btn btn-ghost p-1.5" title="Ubah"><?= icon('edit', 'w-4 h-4') ?></button>
                            <?php endif; ?>
                            <?php if (has_role('Administrator')): ?>
                                <form method="post" action="<?= url('piutang', ['action' => 'destroy', 'id' => $r['id']]) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-ghost p-1.5 text-red-600" onclick="return appConfirmSubmit(event, 'Hapus piutang ini? Data tidak bisa dikembalikan.', 'Hapus')" title="Hapus"><?= icon('trash', 'w-4 h-4') ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= pagination_links($pg) ?>
</div>

<div id="piutangEditModal" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closePiutangEditModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between rounded-t-xl">
            <div>
                <h3 class="font-semibold text-slate-800">Ubah Piutang</h3>
                <p id="piutangEditModalInfo" class="text-xs text-slate-500"></p>
            </div>
            <button type="button" onclick="closePiutangEditModal()" class="btn btn-ghost p-2"><?= icon('x', 'w-5 h-5') ?></button>
        </div>
        <form method="post" id="formPiutangEditModal" action="<?= $piutangUpdateTpl ?>" class="grid grid-cols-1 gap-4 p-6">
            <?= csrf_field() ?>
            <div>
                <label class="label">Total Piutang</label>
                <input type="text" id="piutang_edit_total" class="input" disabled>
            </div>
            <div>
                <label class="label">Jatuh Tempo</label>
                <input type="date" name="jatuh_tempo" id="piutang_edit_jatuh_tempo" class="input">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closePiutangEditModal()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
var _piutangUpdateTpl = <?= json_encode($piutangUpdateTpl) ?>;
function openPiutangEditModal(data){
    var modal=document.getElementById('piutangEditModal');
    var form=document.getElementById('formPiutangEditModal');
    if(!data||!modal||!form) return;
    form.action=_piutangUpdateTpl.replace('__ID__', data.id);
    var info=document.getElementById('piutangEditModalInfo');
    if(info) info.textContent=(data.no_transaksi||'')+' — '+(data.pelanggan||'');
    var totalEl=document.getElementById('piutang_edit_total');
    if(totalEl){
        var v=(data.total||'').toString().replace(/[^0-9]/g,'');
        totalEl.value=v ? Number(v).toLocaleString('id-ID') : (data.total||'');
    }
    var jt=document.getElementById('piutang_edit_jatuh_tempo');
    if(jt) jt.value=(data.jatuh_tempo||'').slice(0,10);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow='hidden';
    setTimeout(function(){ if(jt) jt.focus(); },50);
}
function closePiutangEditModal(){
    var m=document.getElementById('piutangEditModal');
    if(!m) return;
    m.classList.add('hidden');
    m.classList.remove('flex');
    document.body.style.overflow='';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closePiutangEditModal(); });
</script>
