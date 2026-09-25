import { Camera } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { loadQrReader } from '@/lib/qr-reader';

/** How often a video frame is decoded, in milliseconds. */
const DECODE_INTERVAL = 180;

/**
 * The back camera, decoding QR codes until `onCode` accepts one. `onCode` returns whether the value was a
 * voucher; other QR codes are ignored and scanning goes on. While `active` is false the picture stays on but
 * nothing is decoded.
 */
export default function CameraScanner({
    active = true,
    onCode,
}: {
    active?: boolean;
    onCode: (value: string) => boolean;
}) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);
    // Read through a ref, so a new callback on each render does not restart the camera.
    const onCodeRef = useRef(onCode);
    // Also a ref: pausing the decode must not restart the camera, which would flash the picture black.
    const activeRef = useRef(active);
    const [cameraError, setCameraError] = useState('');

    useEffect(() => {
        onCodeRef.current = onCode;
        activeRef.current = active;
    }, [active, onCode]);

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
                        activeRef.current &&
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
        <div className="relative min-h-[22rem] flex-1 overflow-hidden rounded-[2rem] border bg-black shadow-xl sm:min-h-[30rem]">
            <video
                ref={videoRef}
                aria-hidden="true"
                playsInline
                muted
                className="absolute inset-0 size-full object-cover"
            />
            <canvas ref={canvasRef} className="hidden" />
            <div className="pointer-events-none absolute inset-0 bg-[linear-gradient(to_bottom,rgba(0,0,0,.38),transparent_28%,transparent_72%,rgba(0,0,0,.5))]" />
            <div className="pointer-events-none absolute top-1/2 left-1/2 size-56 -translate-x-1/2 -translate-y-1/2 sm:size-64">
                <span className="absolute top-0 left-0 size-12 rounded-tl-3xl border-t-4 border-l-4 border-primary" />
                <span className="absolute top-0 right-0 size-12 rounded-tr-3xl border-t-4 border-r-4 border-primary" />
                <span className="absolute bottom-0 left-0 size-12 rounded-bl-3xl border-b-4 border-l-4 border-primary" />
                <span className="absolute right-0 bottom-0 size-12 rounded-br-3xl border-r-4 border-b-4 border-primary" />
                <span className="absolute top-1/2 right-4 left-4 h-0.5 rounded-full bg-primary/80 shadow-[0_0_12px] shadow-primary motion-safe:animate-scanline" />
            </div>
            {/* Fixed white on black: the caption sits on the camera picture, whatever the theme. */}
            <div className="absolute inset-x-0 bottom-5 flex justify-center">
                <span className="rounded-full bg-black/55 px-4 py-2 text-sm text-white/80 backdrop-blur">
                    Hold the QR code inside the frame
                </span>
            </div>
            {cameraError && (
                <div
                    role="alert"
                    className="absolute inset-0 grid place-items-center bg-background p-8 text-center"
                >
                    <div>
                        <Camera className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-4 max-w-xs text-sm text-muted-foreground">
                            {cameraError}
                        </p>
                    </div>
                </div>
            )}
        </div>
    );
}
