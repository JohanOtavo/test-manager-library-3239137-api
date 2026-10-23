<?php

declare(strict_types=1);

namespace Tests\Unit\Adapters;

use App\Adapters\Http\MultipartBody;
use PHPUnit\Framework\TestCase;

final class MultipartBodyTest extends TestCase
{
    public function test_extrae_el_archivo_del_campo_file(): void
    {
        $body = "--boundary\r\nContent-Disposition: form-data; name=\"file\"; filename=\"cover.png\"\r\n"
            ."Content-Type: image/png\r\n\r\nPNG-DATA\r\n--boundary--\r\n";

        $this->assertSame('PNG-DATA', MultipartBody::fileContents('multipart/form-data; boundary=boundary', $body));
    }

    public function test_preserva_crlf_que_pertenece_al_binario(): void
    {
        $body = "--boundary\r\nContent-Disposition: form-data; name=\"file\"; filename=\"cover.png\"\r\n"
            ."Content-Type: image/png\r\n\r\nPNG-DATA\r\n\r\n--boundary--\r\n";

        $this->assertSame("PNG-DATA\r\n", MultipartBody::fileContents('multipart/form-data; boundary=boundary', $body));
    }

    public function test_devuelve_null_si_no_encuentra_el_campo_file(): void
    {
        $body = "--boundary\r\nContent-Disposition: form-data; name=\"other\"; filename=\"x.png\"\r\n\r\ndata\r\n--boundary--\r\n";

        $this->assertNull(MultipartBody::fileContents('multipart/form-data; boundary=boundary', $body));
    }
}
