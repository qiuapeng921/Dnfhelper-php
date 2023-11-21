<?php

//$cpp_ffi = FFI::cdef(
//    "int Fib(int n);",
//    "C:\Users\Qiuapeng\Desktop\libphp_cpp_ffi.dll");
//
//$start = microtime(true);
//
//$start = microtime(true);
//$c     = 0;
//for ($i = 0; $i < 1000000; $i++) {
//    $c = $cpp_ffi->Fib(12);
//}
//
//echo '[CPP] execution time: ' . (microtime(true) - $start) . ' Result: ' . $c . PHP_EOL;


$ffi = FFI::cdef("
    typedef void *HANDLE;
    void* OpenProcess(
        unsigned long dwDesiredAccess,
        int bInheritHandle,
        unsigned long dwProcessId
    );
    int CloseHandle(void* hObject);
    void* VirtualAllocEx(
        void* hProcess,
        void* lpAddress,
        size_t dwSize,
        unsigned long flAllocationType,
        unsigned long flProtect
    );
    int VirtualFreeEx(
        void* hProcess,
        void* lpAddress,
        size_t dwSize,
        unsigned long dwFreeType
    );
    int ReadProcessMemory(
        void* hProcess,
        const void* lpBaseAddress,
        void* lpBuffer,
        size_t nSize,
        size_t* lpNumberOfBytesRead
    );
    int WriteProcessMemory(
        void* hProcess,
        void* lpBaseAddress,
        const void* lpBuffer,
        size_t nSize,
        size_t* lpNumberOfBytesWritten
    );
", "Kernel32.dll");

// 替换为实际的进程ID
$processId = 9400;

// 打开进程
$processHandle = $ffi->OpenProcess(0x1FFFFF, 0, $processId);

try {
    if ($processHandle !== null) {

        // 分配内存空间
        $allocationSize = 4096;
        $remoteMemory   = $ffi->VirtualAllocEx($processHandle, null, $allocationSize, 0x1000, 0x40);

        if ($remoteMemory !== null) {

            $localBuffer = "Hello, World!";
            $bufferSize  = strlen($localBuffer);
            $bytesRead   = FFI::new("size_t");
            $ffi->WriteProcessMemory($processHandle, $remoteMemory, $localBuffer, $bufferSize, FFI::addr($bytesRead));

            // 从远程进程读取数据
            $bufferSize = 20;
            $buffer     = FFI::new("unsigned char[$bufferSize]");
            $bytesRead  = FFI::new("size_t");
            $ffi->ReadProcessMemory($processHandle, $remoteMemory, $buffer, $bufferSize, FFI::addr($bytesRead));

            // 释放分配的内存
            $ffi->VirtualFreeEx($processHandle, $remoteMemory, 0, 0x8000);
        } else {
            echo "Failed to allocate remote memory.\n";
        }

        // 关闭句柄
        $ffi->CloseHandle($processHandle);
    } else {
        echo "Failed to open process.\n";
    }
} catch (Throwable $throwable) {
    print_r($throwable->getMessage());
}