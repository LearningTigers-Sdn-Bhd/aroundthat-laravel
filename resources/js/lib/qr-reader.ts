import wasmUrl from 'zxing-wasm/reader/zxing_reader.wasm?url';

type ReadBarcodes = typeof import('zxing-wasm/reader').readBarcodes;

let reader: Promise<ReadBarcodes> | null = null;

/**
 * The QR decoder, loaded on first use. Its WebAssembly file is served with the app's own assets, not from a CDN.
 */
export function loadQrReader(): Promise<ReadBarcodes> {
    reader ??= import('zxing-wasm/reader').then(
        ({ prepareZXingModule, readBarcodes }) => {
            prepareZXingModule({
                overrides: {
                    locateFile: (path: string, prefix: string) =>
                        path.endsWith('.wasm') ? wasmUrl : prefix + path,
                },
            });

            return readBarcodes;
        },
    );

    return reader;
}
