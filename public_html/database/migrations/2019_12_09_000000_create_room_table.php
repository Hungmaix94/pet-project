<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->comment('ten phong');
            $table->string('code')->comment('ma so phong');
            $table->string('rental')->comment('so tien de thue phong');
            $table->string('type')->comment('loai phong phong don, phong doi');
            $table->string('status')->comment('trang thai con trong hay k');
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('service_name')->comment('ten dich vu');
            $table->string('service_code')->comment('ma dich vu');
            $table->string('unit_price')->comment('don gia');
            $table->string('fixed_price')->comment('gia co dinh');
            $table->string('unit')->comment('don vi tinh');
            $table->string('type')->comment('loai mac dinh hay khong');
            $table->string('descriptions')->comment('mo ta dich vu');
            $table->timestamps();
        });


        Schema::create('renter', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('identify_id')->comment('Ma the can cuoc cong dan');
            $table->string('phone');
            $table->string('address');
            $table->string('avatar');
            $table->string('domicile')->comment('nguyen quan');
            $table->timestamps();
        });

        Schema::create('landlords', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('address')->comment('Ma the can cuoc cong dan');
            $table->string('phone');
            $table->string('avatar');
            $table->string('domicile')->comment('nguyen quan');
            $table->timestamps();
        });


        Schema::create('renter_rooms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('render_id');
            $table->bigInteger('room_id');
            $table->date('start')->comment('ngay bat dau thue');
            $table->date('expire')->comment('ngay ket thuc');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('failed_jobs');
    }
}
