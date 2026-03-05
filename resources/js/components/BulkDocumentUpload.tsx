import React, { useState, useCallback, useRef } from 'react';
import { useDropzone } from 'react-dropzone';
import { toast } from 'sonner';
import axios from 'axios';

interface UploadedFile {
    id: string;
    file: File;
    progress: number;
    status: 'pending' | 'uploading' | 'completed' | 'error';
    error?: string;
}

interface BulkDocumentUploadProps {
    onUploadComplete?: (batchId: string) => void;
    maxFiles?: number;
    maxFileSize?: number; // in MB
}

const BulkDocumentUpload: React.FC<BulkDocumentUploadProps> = ({
    onUploadComplete,
    maxFiles = 20,
    maxFileSize = 10
}) => {
    const [files, setFiles] = useState<UploadedFile[]>([]);
    const [processingType, setProcessingType] = useState<'auto' | 'project' | 'lot'>('auto');
    const [isUploading, setIsUploading] = useState(false);
    const uploadIdCounter = useRef(0);

    const acceptedFileTypes = {
        'application/pdf': ['.pdf'],
        'image/*': ['.jpg', '.jpeg', '.png', '.gif'],
        'application/msword': ['.doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': ['.docx'],
        'text/plain': ['.txt']
    };

    const onDrop = useCallback((acceptedFiles: File[], rejectedFiles: any[]) => {
        // Handle rejected files
        if (rejectedFiles.length > 0) {
            rejectedFiles.forEach(({ file, errors }) => {
                errors.forEach((error: any) => {
                    if (error.code === 'file-too-large') {
                        toast.error(`${file.name} is too large. Maximum size is ${maxFileSize}MB.`);
                    } else if (error.code === 'file-invalid-type') {
                        toast.error(`${file.name} has an invalid file type. Please upload PDF, image, Word, or text files.`);
                    } else {
                        toast.error(`${file.name}: ${error.message}`);
                    }
                });
            });
        }

        // Handle accepted files
        if (acceptedFiles.length > 0) {
            const currentFileCount = files.length;
            const newFileCount = acceptedFiles.length;

            if (currentFileCount + newFileCount > maxFiles) {
                toast.error(`You can only upload a maximum of ${maxFiles} files. Please remove some files first.`);
                return;
            }

            const newFiles: UploadedFile[] = acceptedFiles.map(file => ({
                id: `file-${uploadIdCounter.current++}`,
                file,
                progress: 0,
                status: 'pending'
            }));

            setFiles(prev => [...prev, ...newFiles]);
            toast.success(`${acceptedFiles.length} file(s) added successfully!`);
        }
    }, [files.length, maxFiles, maxFileSize]);

    const { getRootProps, getInputProps, isDragActive } = useDropzone({
        onDrop,
        accept: acceptedFileTypes,
        maxSize: maxFileSize * 1024 * 1024, // Convert MB to bytes
        disabled: isUploading
    });

    const removeFile = (fileId: string) => {
        setFiles(prev => prev.filter(f => f.id !== fileId));
    };

    const clearAllFiles = () => {
        setFiles([]);
    };

    const formatFileSize = (bytes: number): string => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const uploadFiles = async () => {
        if (files.length === 0) {
            toast.error('Please select files to upload first.');
            return;
        }

        setIsUploading(true);

        try {
            const formData = new FormData();
            files.forEach(({ file }) => {
                formData.append('files[]', file);
            });
            formData.append('processing_type', processingType);

            const response = await axios.post('/api/v1/documents/bulk-upload', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
                onUploadProgress: (progressEvent) => {
                    if (progressEvent.total) {
                        const percentCompleted = Math.round(
                            (progressEvent.loaded * 100) / progressEvent.total
                        );

                        // Update progress for all files
                        setFiles(prev => prev.map(file => ({
                            ...file,
                            progress: percentCompleted,
                            status: percentCompleted === 100 ? 'completed' : 'uploading'
                        })));
                    }
                }
            });

            if (response.data.success) {
                toast.success(response.data.message);
                setFiles([]);
                onUploadComplete?.(response.data.data.batch_id);
            } else {
                throw new Error(response.data.message || 'Upload failed');
            }
        } catch (error: any) {
            console.error('Upload error:', error);

            // Update file statuses to error
            setFiles(prev => prev.map(file => ({
                ...file,
                status: 'error',
                error: error.response?.data?.message || error.message || 'Upload failed'
            })));

            toast.error(error.response?.data?.message || 'Upload failed. Please try again.');
        } finally {
            setIsUploading(false);
        }
    };

    return (
        <div className="w-full max-w-4xl mx-auto p-6">
            {/* Processing Type Selection */}
            <div className="mb-6">
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Processing Type
                </label>
                <select
                    value={processingType}
                    onChange={(e) => setProcessingType(e.target.value as any)}
                    disabled={isUploading}
                    className="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                    <option value="auto">Auto-detect (Recommended)</option>
                    <option value="project">Project Information</option>
                    <option value="lot">Lot/Property Information</option>
                </select>
                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Choose how you want the documents to be processed. Auto-detect will analyze content and choose the best type.
                </p>
            </div>

            {/* Drag & Drop Area */}
            <div
                {...getRootProps()}
                className={`
                    border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer
                    ${isDragActive
                        ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20'
                        : 'border-gray-300 hover:border-gray-400 dark:border-gray-600 dark:hover:border-gray-500'
                    }
                    ${isUploading ? 'opacity-50 cursor-not-allowed' : ''}
                `}
            >
                <input {...getInputProps()} />

                <div className="space-y-4">
                    <svg
                        className="mx-auto h-12 w-12 text-gray-400"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 48 48"
                    >
                        <path
                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                            strokeWidth={2}
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>

                    <div>
                        <p className="text-lg font-medium text-gray-900 dark:text-white">
                            {isDragActive ? 'Drop files here' : 'Drag & drop files here'}
                        </p>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            or <span className="text-blue-600 hover:text-blue-700 font-medium">browse files</span>
                        </p>
                    </div>

                    <div className="text-xs text-gray-400 dark:text-gray-500 space-y-1">
                        <p>Supported: PDF, JPG, PNG, DOC, DOCX, TXT</p>
                        <p>Maximum: {maxFiles} files, {maxFileSize}MB each</p>
                    </div>
                </div>
            </div>

            {/* File List */}
            {files.length > 0 && (
                <div className="mt-6">
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                            Selected Files ({files.length}/{maxFiles})
                        </h3>
                        <button
                            onClick={clearAllFiles}
                            disabled={isUploading}
                            className="text-sm text-red-600 hover:text-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Clear All
                        </button>
                    </div>

                    <div className="space-y-2 max-h-64 overflow-y-auto">
                        {files.map((uploadedFile) => (
                            <div
                                key={uploadedFile.id}
                                className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                            >
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {uploadedFile.file.name}
                                    </p>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        {formatFileSize(uploadedFile.file.size)}
                                    </p>
                                    {uploadedFile.status === 'error' && uploadedFile.error && (
                                        <p className="text-xs text-red-500 mt-1">
                                            {uploadedFile.error}
                                        </p>
                                    )}
                                </div>

                                <div className="flex items-center space-x-3">
                                    {/* Progress indicator */}
                                    {uploadedFile.status === 'uploading' && (
                                        <div className="w-16 text-xs text-gray-500">
                                            {uploadedFile.progress}%
                                        </div>
                                    )}

                                    {/* Status indicator */}
                                    <div className="w-4 h-4">
                                        {uploadedFile.status === 'pending' && (
                                            <div className="w-4 h-4 rounded-full bg-gray-300"></div>
                                        )}
                                        {uploadedFile.status === 'uploading' && (
                                            <div className="w-4 h-4 rounded-full bg-blue-500 animate-pulse"></div>
                                        )}
                                        {uploadedFile.status === 'completed' && (
                                            <svg className="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                            </svg>
                                        )}
                                        {uploadedFile.status === 'error' && (
                                            <svg className="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                            </svg>
                                        )}
                                    </div>

                                    {/* Remove button */}
                                    <button
                                        onClick={() => removeFile(uploadedFile.id)}
                                        disabled={isUploading}
                                        className="text-red-500 hover:text-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Upload Button */}
            {files.length > 0 && (
                <div className="mt-6 flex justify-end">
                    <button
                        onClick={uploadFiles}
                        disabled={isUploading || files.length === 0}
                        className={`
                            px-6 py-3 rounded-lg font-medium transition-colors
                            ${isUploading || files.length === 0
                                ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                : 'bg-blue-600 hover:bg-blue-700 text-white'
                            }
                        `}
                    >
                        {isUploading ? 'Uploading...' : `Upload ${files.length} File${files.length > 1 ? 's' : ''}`}
                    </button>
                </div>
            )}
        </div>
    );
};

export default BulkDocumentUpload;