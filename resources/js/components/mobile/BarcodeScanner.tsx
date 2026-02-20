/**
 * BarcodeScanner Component - Mobile barcode/QR code scanner
 * Wraps ZXing.js for real-time barcode detection
 * Requires: npm install @zxing/library
 */

import { useEffect, useRef, useState } from 'react';

interface BarcodeScannerProps {
    onScan: (result: string) => void;
    onError?: (error: string) => void;
    autoFocus?: boolean;
    facingMode?: 'environment' | 'user';
    supportedFormats?: string[];
}

interface ScanResult {
    text: string;
    format: string;
    timestamp: number;
}

export function BarcodeScanner({
    onScan,
    onError,
    autoFocus = true,
    facingMode = 'environment',
    supportedFormats = [
        'QR_CODE',
        'CODE_128',
        'CODE_39',
        'EAN_13',
        'EAN_8',
        'UPC_A',
        'AZTEC',
        'DATA_MATRIX',
        'PDF_417',
    ],
}: BarcodeScannerProps) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const [isScanning, setIsScanning] = useState(false);
    const [hasPermission, setHasPermission] = useState<boolean | null>(null);
    const [lastScannedCode, setLastScannedCode] = useState<ScanResult | null>(
        null,
    );
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        let isMounted = true;

        const initializeCamera = async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode,
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                    },
                    audio: false,
                });

                if (isMounted && videoRef.current) {
                    videoRef.current.srcObject = stream;
                    setHasPermission(true);
                    setIsScanning(true);

                    const track = stream.getVideoTracks()[0];
                    const settings = track.getSettings?.();
                    if (autoFocus && settings && 'focusMode' in settings) {
                        try {
                            await track.applyConstraints({
                                advanced: [{ focusMode: 'continuous' }],
                            } as MediaTrackConstraints);
                        } catch {
                            // Focus mode not supported
                        }
                    }
                }
            } catch (err) {
                const errorMessage =
                    err instanceof Error
                        ? err.message
                        : 'Failed to access camera';
                if (isMounted) {
                    setHasPermission(false);
                    setError(errorMessage);
                    onError?.(errorMessage);
                }
            }
        };

        initializeCamera();

        return () => {
            isMounted = false;
            if (videoRef.current?.srcObject) {
                const stream = videoRef.current.srcObject as MediaStream;
                stream.getTracks().forEach((track) => track.stop());
            }
        };
    }, [facingMode, autoFocus, onError]);

    useEffect(() => {
        if (!isScanning || !videoRef.current || !canvasRef.current) {
            return;
        }

        // Try to load ZXing library
        const loadZXing = async () => {
            try {
                // @ts-expect-error - ZXing types may be incomplete at runtime
                const {
                    BrowserMultiFormatReader,
                    NotFoundException,
                    ChecksumException,
                    FormatException,
                } = await import('@zxing/library');

                const codeReader = new BrowserMultiFormatReader();
                let continuousScanning = true;

                const scanFrame = async () => {
                    if (!continuousScanning || !videoRef.current) {
                        return;
                    }

                    try {
                        if (
                            videoRef.current.readyState ===
                            HTMLMediaElement.HAVE_ENOUGH_DATA
                        ) {
                            const canvas = canvasRef.current;
                            if (!canvas) return;

                            const ctx = canvas.getContext('2d');
                            if (!ctx) return;

                            canvas.width = videoRef.current.videoWidth;
                            canvas.height = videoRef.current.videoHeight;

                            ctx.drawImage(
                                videoRef.current,
                                0,
                                0,
                                canvas.width,
                                canvas.height,
                            );

                            try {
                                const result =
                                    codeReader.decodeFromCanvas(canvas);

                                if (
                                    result &&
                                    supportedFormats.includes(
                                        result.getBarcodeFormat(),
                                    )
                                ) {
                                    const scannedText = result.getText();
                                    const now = Date.now();

                                    // Avoid duplicate scans within 1 second
                                    if (
                                        !lastScannedCode ||
                                        lastScannedCode.text !== scannedText ||
                                        now - lastScannedCode.timestamp > 1000
                                    ) {
                                        setLastScannedCode({
                                            text: scannedText,
                                            format: result.getBarcodeFormat(),
                                            timestamp: now,
                                        });
                                        onScan(scannedText);
                                    }
                                }
                            } catch (err) {
                                // Barcode not found in frame, continue scanning
                                if (
                                    !(err instanceof NotFoundException) &&
                                    !(err instanceof ChecksumException) &&
                                    !(err instanceof FormatException)
                                ) {
                                    throw err;
                                }
                            }
                        }
                    } catch (err) {
                        const errorMessage =
                            err instanceof Error ? err.message : 'Scan error';
                        setError(errorMessage);
                        onError?.(errorMessage);
                    } finally {
                        if (continuousScanning) {
                            requestAnimationFrame(scanFrame);
                        }
                    }
                };

                scanFrame();

                return () => {
                    continuousScanning = false;
                };
            } catch {
                const errorMessage =
                    'ZXing library not installed. Install with: npm install @zxing/library';
                setError(errorMessage);
                onError?.(errorMessage);
            }
        };

        loadZXing();
    }, [isScanning, supportedFormats, lastScannedCode, onScan, onError]);

    return (
        <div className="flex w-full flex-col gap-2">
            {hasPermission === null && (
                <div className="rounded bg-blue-50 p-3 text-sm text-blue-700">
                    Requesting camera permission...
                </div>
            )}

            {hasPermission === false && (
                <div className="rounded bg-red-50 p-3 text-sm text-red-700">
                    {error ||
                        'Camera permission denied. Check browser settings.'}
                </div>
            )}

            {hasPermission === true && (
                <>
                    <div className="relative w-full overflow-hidden rounded-lg bg-black">
                        <video
                            ref={videoRef}
                            autoPlay
                            playsInline
                            className="aspect-video w-full"
                            style={{ transform: 'scaleX(-1)' }}
                        />
                        <canvas ref={canvasRef} className="hidden" />

                        {/* Scanning frame overlay */}
                        <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                            <div className="h-64 w-64 rounded-lg border-2 border-green-500 opacity-50" />
                        </div>

                        {/* Status indicator */}
                        <div className="bg-opacity-50 absolute top-3 left-3 rounded bg-black px-3 py-1 text-xs text-white">
                            {lastScannedCode ? 'Scanning...' : 'Ready'}
                        </div>
                    </div>

                    {lastScannedCode && (
                        <div className="rounded bg-green-50 p-3 text-sm">
                            <p className="font-medium text-green-900">
                                Last Scan:
                            </p>
                            <p className="font-mono text-lg break-all text-green-700">
                                {lastScannedCode.text}
                            </p>
                            <p className="mt-1 text-xs text-green-600">
                                Format: {lastScannedCode.format}
                            </p>
                        </div>
                    )}

                    {error && (
                        <div className="rounded bg-red-50 p-3 text-sm text-red-700">
                            {error}
                        </div>
                    )}
                </>
            )}
        </div>
    );
}

export default BarcodeScanner;
