<?php // Checkout View ?>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="row">
    <div class="col-lg-6">
        <?= form_open('buy', 'class="row g-3"') ?>
        <?= form_hidden('username', session()->get('username')) ?>
        <?= form_input(['type' => 'hidden', 'name' => 'total_harga', 'id' => 'total_harga', 'value' => '']) ?>
        <?= form_input(['type' => 'hidden', 'name' => 'ppn', 'id' => 'ppn', 'value' => '']) ?>
        <?= form_input(['type' => 'hidden', 'name' => 'biaya_admin', 'id' => 'biaya_admin', 'value' => '']) ?>
        <?= form_input(['type' => 'hidden', 'name' => 'ongkir', 'id' => 'ongkir', 'value' => '']) ?>

        <div class="col-12">
            <label for="nama" class="form-label">Nama</label>
            <input type="text" class="form-control" id="nama" value="<?= session()->get('username'); ?>" readonly>
        </div>
        <div class="col-12">
            <label for="alamat" class="form-label">Alamat</label>
            <input type="text" class="form-control" id="alamat" name="alamat" required>
        </div> 
        <div class="col-12">
            <label for="kelurahan" class="form-label">Kelurahan</label>
            <select class="form-control" id="kelurahan" name="kelurahan" required></select>
        </div>
        <div class="col-12">
            <label for="layanan" class="form-label">Layanan</label>
            <select class="form-control" id="layanan" name="layanan" required></select>
        </div>
        <div class="col-12">
            <label for="ongkir_display" class="form-label">Ongkir</label>
            <input type="text" class="form-control" id="ongkir_display" readonly>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="col-12">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Sub Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)) :
                        foreach ($items as $item) : ?>
                            <tr>
                                <td><?= $item['name'] ?></td>
                                <td><?= number_to_currency($item['price'], 'IDR') ?></td>
                                <td><?= $item['qty'] ?></td>
                                <td><?= number_to_currency($item['price'] * $item['qty'], 'IDR') ?></td>
                            </tr>
                    <?php endforeach; endif; ?>
                    <tr>
                        <td colspan="2"></td>
                        <td>Subtotal</td>
                        <td><?= number_to_currency($total, 'IDR') ?></td>
                    </tr>
                    <tr>
                        <td colspan="2"></td>
                        <td>PPN (11%)</td>
                        <td><span id="ppn_display">IDR 0</span></td>
                    </tr>
                    <tr>
                        <td colspan="2"></td>
                        <td>Ongkir</td>
                        <td><span id="ongkir_display_table">IDR 0</span></td>
                    </tr>
                    <tr>
                        <td colspan="2"></td>
                        <td>Biaya Admin</td>
                        <td><span id="biaya_admin_display">IDR 0</span></td>
                    </tr>
                    <tr>
                        <td colspan="2"></td>
                        <td>Grand Total</td>
                        <td><span id="grand_total"><?= number_to_currency($total, 'IDR') ?></span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="text-center">
            <button type="submit" class="btn btn-primary">Buat Pesanan</button>
        </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('script') ?>
<script>
$(document).ready(function() {
    var ongkir = 0;

    hitungTotal();

    $('#kelurahan').select2({
        placeholder: 'Ketik nama kelurahan...',
        ajax: {
            url: '<?= base_url('get-location') ?>',
            dataType: 'json',
            delay: 1500,
            data: function (params) {
                return { search: params.term };
            },
            processResults: function (data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.id,
                            text: item.subdistrict_name + ", " + item.district_name + ", " + item.city_name + ", " + item.province_name + ", " + item.zip_code
                        };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 3
    });

    $("#kelurahan").on('change', function() {
        var id_kelurahan = $(this).val(); 
        $("#layanan").empty();
        ongkir = 0;

        $.ajax({
            url: "<?= site_url('get-cost') ?>",
            type: 'GET',
            data: { 'destination': id_kelurahan },
            dataType: 'json',
            success: function(data) { 
                data.forEach(function(item) {
                    var text = item["description"] + " (" + item["service"] + ") : estimasi " + item["etd"];
                    $("#layanan").append($('<option>', {
                        value: item["cost"],
                        text: text 
                    }));
                });
                hitungTotal(); 
            },
        });
    });

    $("#layanan").on('change', function() {
        ongkir = parseInt($(this).val());
        hitungTotal();
    });  

    function hitungTotal() {
        var subtotal = <?= $total ?>; 
        var subtotalOngkir = subtotal + ongkir;

        var biayaAdmin = 0;
        if (subtotal <= 10000000) {
            biayaAdmin = subtotal * 0.01;
        } else if (subtotal <= 50000000) {
            biayaAdmin = subtotal * 0.008;
        } else {
            biayaAdmin = subtotal * 0.006;
        }

        var ppn = subtotalOngkir * 0.11;
        var grandTotal = subtotalOngkir + ppn + biayaAdmin;

        $("#ongkir").val(ongkir);
        $("#ppn").val(ppn);
        $("#biaya_admin").val(biayaAdmin);

        $("#biaya_admin_display").html("IDR " + biayaAdmin.toFixed(0).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
        $("#ppn_display").html("IDR " + ppn.toFixed(0).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
        $("#ongkir_display_table").html("IDR " + ongkir.toFixed(0).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
        $("#grand_total").html("IDR " + grandTotal.toFixed(0).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));

        $("#total_harga").val(grandTotal);
        $("#ongkir_display").val("IDR " + ongkir.toFixed(0).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,'));
    }
});
</script>
<?= $this->endSection() ?>
