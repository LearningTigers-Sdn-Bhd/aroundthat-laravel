import { Camera } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { loadQrReader } from '@/lib/qr-reader';

/** How often a video frame is decoded, in milliseconds. */
const DECODE_INTERVAL = 180;

/**
 * The back camera, decoding QR codes until `onCode` accepts one. `onCode` returns whether the value was a
 * voucher; other QR codes are ignored and scanning goes on.
 */
export default function CameraScanner({
    onCode,
}: {
    onCode: (value: string) => boolean;
}) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);
    // Read through a ref, so a new callback on each render does not restart the camera.
    const onCodeRef = useRef(onCode);
    const [cameraError, setCameraError] = useState('');

    useEffect(() => {
        onCodeRef.current = onCode;
    }, [onCode]);

    useEffect(() => {
        let stream: MediaStream | null = null;
        let frame = 0;
        let cancelled = false;
        let decoding = false;
        let detected = false;
        let lastDecodedAt = 0;
        const video = videoRef.current;

        const start = async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' } },
                    audio: false,
                });

                if (cancelled || !video) {
                    stream.getTracks().forEach((track) => track.stop());

                    return;
                }

                video.srcObject = stream;
                await video.play();
                const readBarcodes = await loadQrReader();

                const scan = (timestamp: number) => {
                    if (cancelled || detected) {
                        return;
                    }

                    const canvas = canvasRef.current;

                    if (
                        canvas &&
                        video.readyState >=
                            HTMLMediaElement.HAVE_CURRENT_DATA &&
                        !decoding &&
                        timestamp - lastDecodedAt >= DECODE_INTERVAL
                    ) {
                        decoding = true;
                        lastDecodedAt = timestamp;
                        const width = Math.min(video.videoWidth, 720);
                        const height = Math.round(
                            (video.videoHeight / video.videoWidth) * width,
                        );
                        canvas.width = width;
                        canvas.height = height;
                        const context = canvas.getContext('2d', {
                            willReadFrequently: true,
                        });

                        if (context && width > 0 && height > 0) {
                            context.drawImage(video, 0, 0, width, height);

                            void readBarcodes(
                                context.getImageData(0, 0, width, height),
                                { formats: ['QRCode'], maxNumberOfSymbols: 1 },
                            )
                                .then((codes) => {
                                    if (
                                        codes[0]?.text &&
                                        onCodeRef.current(codes[0].text)
                                    ) {
                                        detected = true;
                                    }
                                })
                                .catch(() => undefined)
                                .finally(() => {
                                    decoding = false;
                                });
                        } else {
                            decoding = false;
                        }
                    }

                    frame = requestAnimationFrame(scan);
                };

                frame = requestAnimationFrame(scan);
            } catch {
                if (!cancelled) {
                    setCameraError(
                        'The camera is not available. Type the voucher code instead.',
                    );
                }
            }
        };

        void start();

        return () => {
            cancelled = true;
            cancelAnimationFrame(frame);
            stream?.getTracks().forEach((track) => track.stop());

            if (video) {
                video.srcObject = null;
            }
        };
    }, []);

    return (
        <div className="relative aspect-[4/3] w-full overflow-hidden rounded-md border bg-black">
            <video
                ref={videoRef}
                aria-hidden="true"
                playsInline
                muted
                className="absolute inset-0 size-full object-cover"
            />
            <canvas ref={canvasRef} className="hidden" />
            <div className="pointer-events-none absolute top-1/2 left-1/2 size-48 -translate-x-1/2 -translate-y-1/2 rounded-2xl border-4 border-white/80" />
            {/* Fixed white on black: the caption sits on the camera picture, whatever the theme. */}
            <p className="absolute inset-x-0 bottom-3 text-center text-sm text-white/85">
                Hold the QR code inside the frame
            </p>
            {cameraError && (
                <div
                    role="alert"
                    className="absolute inset-0 grid place-items-center bg-background p-6 text-center"
                >
                    <div>
                        <Camera className="mx-auto size-8 text-muted-foreground" />
                        <p className="mt-3 max-w-xs text-sm text-muted-foreground">
                            {cameraError}
                        </p>
                    </div>
                </div>
            )}
        </div>
    );
}
