export const downloadCanvas = (canvas1: HTMLCanvasElement | null, string: string) => {
    if (!canvas1) return;
    let dat = canvas1.toDataURL('image/png');
    dat = dat.replace(/^data:image\/[^;]*/, 'data:application/octet-stream');
    const dtDownload = dat.replace(
        /^data:application\/octet-stream/,
        'data:application/octet-stream;headers=Content-Disposition%3A%20attachment%3B%20filename=Canvas'
    );
    const theLink = document.createElement('a');
    const fileName = string.replaceAll(' ', '-');
    theLink.download = fileName.toLowerCase() + '.png';
    theLink.href = dtDownload;
    theLink.click();
};
