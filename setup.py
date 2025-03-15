from setuptools import setup, find_packages

setup(
    name="documentador",
    version="0.1",
    packages=find_packages(),
    install_requires=[
        'pathlib',
        'typing',
    ]
)