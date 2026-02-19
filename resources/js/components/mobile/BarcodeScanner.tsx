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
  const [lastScannedCode, setLastScannedCode] = useState<ScanResult | null>(null);
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

          if (autoFocus && 'focusMode' in stream.getVideoTracks()[0].getSettings?.()) {
            try {
              const track = stream.getVideoTracks()[0];
              await (track as any).applyConstraints({
                advanced: [{ focusMode: 'continuous' }],
              });
            } catch {
              // Focus mode not supported
            }
          }
        }
      } catch (err) {
        const errorMessage =
          err instanceof Error ? err.message : 'Failed to access camera';
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
        // @ts-ignore - ZXing library loading
        const { BrowserMultiFormatReader, NotFoundException, ChecksumException, FormatException } = await import(
          '@zxing/library'
        );

        const codeReader = new BrowserMultiFormatReader();
        let continuousScanning = true;

        const scanFrame = async () => {
          if (!continuousScanning || !videoRef.current) {
            return;
          }

          try {
            if (videoRef.current.readyState === HTMLMediaElement.HAVE_ENOUGH_DATA) {
              const canvas = canvasRef.current;
              if (!canvas) return;

              const ctx = canvas.getContext('2d');
              if (!ctx) return;

              canvas.width = videoRef.current.videoWidth;
              canvas.height = videoRef.current.videoHeight;

              ctx.drawImage(videoRef.current, 0, 0, canvas.width, canvas.height);

              const imageData = ctx.getImageData(
                0,
                0,
                canvas.width,
                canvas.height
              );

              try {
                const result = codeReader.decodeFromCanvas(canvas);

                if (result && supportedFormats.includes(result.getBarcodeFormat())) {
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
      } catch (err) {
        const errorMessage =
          'ZXing library not installed. Install with: npm install @zxing/library';
        setError(errorMessage);
        onError?.(errorMessage);
      }
    };

    loadZXing();
  }, [isScanning, supportedFormats, lastScannedCode, onScan, onError]);

  return (
    <div className="flex flex-col gap-2 w-full">
      {hasPermission === null && (
        <div className="bg-blue-50 p-3 rounded text-sm text-blue-700">
          Requesting camera permission...
        </div>
      )}

      {hasPermission === false && (
        <div className="bg-red-50 p-3 rounded text-sm text-red-700">
          {error || 'Camera permission denied. Check browser settings.'}
        </div>
      )}

      {hasPermission === true && (
        <>
          <div className="relative w-full bg-black rounded-lg overflow-hidden">
            <video
              ref={videoRef}
              autoPlay
              playsInline
              className="w-full aspect-video"
              style={{ transform: 'scaleX(-1)' }}
            />
            <canvas
              ref={canvasRef}
              className="hidden"
            />

            {/* Scanning frame overlay */}
            <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
              <div className="w-64 h-64 border-2 border-green-500 rounded-lg opacity-50" />
            </div>

            {/* Status indicator */}
            <div className="absolute top-3 left-3 bg-black bg-opacity-50 px-3 py-1 rounded text-xs text-white">
              {lastScannedCode ? 'Scanning...' : 'Ready'}
            </div>
          </div>

          {lastScannedCode && (
            <div className="bg-green-50 p-3 rounded text-sm">
              <p className="font-medium text-green-900">Last Scan:</p>
              <p className="text-green-700 font-mono text-lg break-all">
                {lastScannedCode.text}
              </p>
              <p className="text-xs text-green-600 mt-1">
                Format: {lastScannedCode.format}
              </p>
            </div>
          )}

          {error && (
            <div className="bg-red-50 p-3 rounded text-sm text-red-700">
              {error}
            </div>
          )}
        </>
      )}
    </div>
  );
}

export default BarcodeScanner;
